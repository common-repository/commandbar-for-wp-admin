<?php

function commandbar_serialize_post($post, $icon = NULL, $include_post_meta = FALSE)
{
  global $wp_post_types;
  global $wp_post_statuses;
  $is_editor = current_user_can($wp_post_types[$post->post_type]->cap->edit_post, $post);
  $post_content = apply_filters('the_content', $post->post_content);
  $post_meta = get_post_meta($post->ID);

  $serialized = [
    "id" => $post->ID,
    "title" => html_entity_decode($post->post_title),
    "type" => $post->post_type,
    "type_label" => $wp_post_types[$post->post_type]->labels->singular_name,
    "type_label_plural" => $wp_post_types[$post->post_type]->labels->name,
    "status" =>  $post->post_status,
    "status_label" =>  $wp_post_statuses[$post->post_status]->label,
    "content" => html_entity_decode(wp_strip_all_tags($post_content))/* . ($include_post_meta ? ("\n" . json_encode($post_meta, JSON_PRETTY_PRINT)) : "")*/,
    "content_html" => $post_content/* . ($include_post_meta ? ("\n" . json_encode($post_meta, JSON_PRETTY_PRINT)) : "")*/,
    "permalink" => $is_editor ? get_edit_post_link($post->ID, '') : get_permalink($post),
    "edit_link" => get_edit_post_link($post->ID, ''),
    "view_link" => get_permalink($post),
    "meta" => $post_meta
  ];

  if($wp_post_types[$post->post_type]->menu_icon) {
    $serialized['icon'] = commandbar_make_icon_string($wp_post_types[$post->post_type]->menu_icon);
  } elseif($icon) {
    $serialized['icon'] = $icon;
  }

  // add more fields to certain types of 
  if ($post->post_type === 'shop_order') {
    // TODO
  }

  if ($post->post_type === 'attachment') {
    $serialized["attachment_url"] = wp_get_attachment_url($post->ID);
    $serialized["__extraHTML"] = "<img height='64' src='" . esc_attr(wp_get_attachment_thumb_url($post->ID)) . "' />";
  }


  if ($post->menu_order) {
    $serialized["sort_key"] = $post->menu_order;
  }

  return $serialized;
}

// returns text nodes from the root of the provided $html concatenated together
// e.g. turns "foo <span>bar <span>baz</span></span> biz" into "foo  biz"
function commandbar_only_text_nodes_in_root($html)
{
  if (!$html) return NULL;

  $dom = new DOMDocument();
  $dom->loadHTML($html);
  $root = $dom->lastChild->firstChild->firstChild;
  foreach ($root->childNodes as $child) {
    if ($child->nodeType == XML_TEXT_NODE) continue;
    $root->removeChild($child);
  }
  return $root->textContent;
}

function commandbar_make_icon_string($icon)
{
  if(!$icon) return NULL;

  if($icon == 'div') return NULL;

  if (str_starts_with($icon, "dashicons-")) {
    return '<span class="dashicons ' . esc_attr($icon) . '"></span>';
  } else {
    return '<img width="20" src="' . esc_attr($icon) . '" />';
  }

  return NULL;
}


// see wp-admin/menu-header.php
function commandbar_menu_url($item)
{
  global $submenu;

  if (false !== strpos($item[4], 'wp-menu-separator')) {
    return NULL;
  }

  $submenu_items = array();
  if (!empty($submenu[$item[2]])) {
    $submenu_items = $submenu[$item[2]];
  }

  if (!empty($submenu_items)) {
    $submenu_items = array_values($submenu_items);  // Re-index.
    $menu_hook     = get_plugin_page_hook($submenu_items[0][2], $item[2]);
    $menu_file     = $submenu_items[0][2];
    $pos           = strpos($menu_file, '?');

    if (false !== $pos) {
      $menu_file = substr($menu_file, 0, $pos);
    }

    if (
      !empty($menu_hook)
      || (('index.php' !== $submenu_items[0][2])
        && file_exists(WP_PLUGIN_DIR . "/$menu_file")
        && !file_exists(ABSPATH . "/wp-admin/$menu_file"))
    ) {
      return "admin.php?page={$submenu_items[0][2]}";
    } else {
      return $submenu_items[0][2];
    }
  } elseif (!empty($item[2]) && current_user_can($item[1])) {
    $menu_hook = get_plugin_page_hook($item[2], 'admin.php');
    $menu_file = $item[2];
    $pos       = strpos($menu_file, '?');

    if (false !== $pos) {
      $menu_file = substr($menu_file, 0, $pos);
    }

    if (
      !empty($menu_hook)
      || (('index.php' !== $item[2])
        && file_exists(WP_PLUGIN_DIR . "/$menu_file")
        && !file_exists(ABSPATH . "/wp-admin/$menu_file"))
    ) {
      return "admin.php?page={$item[2]}";
    } else {
      return $item[2];
    }
  }
}

