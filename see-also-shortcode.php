<?php
///*
// * Plugin Name: See Also Shortcode
// * Description: When you have external urls that you want to iframe on a different page
// * Version: 1.0
// * Author: R. Krathwohl
// */


if (!function_exists('see_also_shortcode')) :

  function setup_see_also_shortcode() {
    wp_register_style('see-also-shortcode-css', plugins_url('see-also-shortcode-css.css', __FILE__));
    wp_enqueue_style('see-also-shortcode-css');
  }
  add_action('wp_enqueue_scripts', 'setup_see_also_shortcode');

  /**
   * See Also Shortcode takes in attributes & content
   * content -> the title of the link desired
   * atts:
   *  link_id -> name of the meta value
   *  link_to -> which page to go to that has the iframe involved
   *  optional: (one or the other)
   *   current_page_id - id of the page the article is on
   *   current_page_title - title of page the article is on
   */
  function see_also_link_shortcode($atts, $content = null) {
    extract(shortcode_atts(array(
      'link_id' => 'see-also-1',
      'link_to' => 'see_also',
      'current_page_id' => 0,
      'current_page_title' => '',
      'show_arrow' => 'true',
      'class' => 'see_also_link'
    ), $atts));

    $current_page_id = get_current_page_id($current_page_id, $current_page_title);
    if ($current_page_id == 0) {
      trigger_error('No page id given outside The Loop - put this shortcode inside The Loop or ' .
        'include "current_page_id" or "current_page_title" in the shortcode attributes');
    }

    $link = get_permalink(get_page_by_path($link_to)->ID);

    $full_link = add_query_arg(array('parent' => $current_page_id,
      'meta' => get_meta_id_by_key($current_page_id, $link_id)), $link);

    $return =  '<p class="'. $class .'"><a href="'. $full_link . '">' . $content;

    if ($show_arrow != 'false') {
      $return .= '<span class="curvedarrow"></span>';
    }

    $return .= '</a></p>';
    return  $return;
  }
  add_shortcode('see-also-link', 'see_also_link_shortcode');

  /** Thank you, wpseek.com*/
  function get_meta_id_by_key($post_id, $meta_key) {
    global $wpdb;
    $mid = $wpdb->get_var($wpdb->prepare("SELECT meta_id FROM $wpdb->postmeta WHERE post_id = %d AND meta_key = %s", $post_id, $meta_key));

    if($mid != '')
      return (int)$mid;

    return false;
  }

  function get_current_page_id($current_page_id, $current_page_title) {
    if (!empty($current_page_id)) {
      return $current_page_id;
    }
    if (!empty($current_page_title)) {
      $page = get_page_by_title($current_page_title);
      if ($page) {
        return $page->ID;
      }
    }
    if (in_the_loop()) {
      return get_the_ID();
    }
    return 0;
  }

  function see_also_page_template() {
    $parent_post_id = array_key_exists('parent', $_GET) ? $_GET['parent'] : 0;

    if (array_key_exists('meta', $_GET)) {
      $meta_id = $_GET['meta'];
    } else {
      if (is_page('video')) {
        $meta_id = get_meta_id_by_key($parent_post_id, 'external_video_url');
      }
    }

    if (empty($meta_id)) {
      show_error_message('missing_meta_param', $parent_post_id);
    }

    $external_url_meta = get_metadata_by_mid('post', $meta_id);

    if (!empty($external_url_meta->meta_value)) {
      echo do_shortcode('[iframe src="' . $external_url_meta->meta_value . '" width="100% height="100%]');
    } else {
      show_error_message('missing_url', $parent_post_id);
    }
  }
  add_shortcode('see-also-page', 'see_also_page_template');

  function show_error_message($error_type, $parent_post_id) {
    if (is_admin()) {
      return;
    }

    if ($error_type = 'missing_url') {
      trigger_error("The URL associated with this link does not exist. " .
        "Please look at the parent post to make sure this link works/exists.");
    } elseif ($error_type == 'missing_meta_param') {
      trigger_error("The value that identifies the link used on this page is missing.  Please check the URL's meta parameter.");
    } else {
      trigger_error('An unexpected error has occurred.');
    }

    echo 'We apologize but we are currently experiencing difficulties. '.
      'Click to return to <a style="text-decoration: underline" href="'. get_permalink($parent_post_id).'">' .
      get_post_field('post_title', $parent_post_id) . '</a>.';
  }

endif;