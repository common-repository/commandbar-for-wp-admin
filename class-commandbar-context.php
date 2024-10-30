<?php

class CommandBar_Context
{
    public function __construct()
    {
        if(COMMANDBAR_ENABLE_FOR_NON_ADMIN_USERS) {
          add_action('wp_head', [$this, 'context']);
        }
        if(COMMANDBAR_ENABLE_FOR_ADMIN_USERS) {
            add_action('admin_print_scripts', [$this, 'context_for_admin']);
        }
    }

    function serialize_current_user()
    {
      $user = wp_get_current_user();
      $serialized_user = [
        'id' => $user->id,
        'display_name' => $user->data->display_name,
        'login' => $user->data->user_login,
        'nicename' => $user->data->user_nicename,
        'email' => $user->data->user_email,
        'url' => $user->data->user_url,
        'registered' => $user->data->user_registered,
        'status' => $user->data->user_status,
        'caps' => array_keys(
          array_filter($user->caps, fn ($value) => !!$value, ARRAY_FILTER_USE_BOTH)
        ),
        'roles' => $user->roles,
        'allcaps' => array_keys(
          array_filter($user->allcaps, fn ($value) => !!$value, ARRAY_FILTER_USE_BOTH)
        )
      ];
      return $serialized_user;
    }
    
    function context_for_admin()
    {
        return $this->context(true);
    }

    public function context($viewing_admin = false) {
        global $wpdb;
        $options = commandbar_get_options();

        $query = new WP_Query([
          'post_type' => 'page',
          'perm' => 'readable',
          'nopaging' => true,
          'posts_per_page' => -1,
          'meta_query' => array(  
            'relation' => 'OR',
            array(
                'key' => 'commandbar_omit',
                'compare' => 'NOT EXISTS'
            ),
            array(
              'key' => 'commandbar_omit',
              'value' => 'true',
              'compare' => '!='
          )
        )
        ]);
        $pages = $query->posts;
        usort($pages, function($a, $b) {
          $a_menu_order = $a->menu_order ? $a->menu_order : 0;
          $b_menu_order = $b->menu_order ? $b->menu_order : 0;
      
          if($a_menu_order != $b_menu_order) {
            return $a_menu_order - $b_menu_order;
          }
          return strnatcmp(html_entity_decode($a->post_title), html_entity_decode($b->post_title));
        });
        $pages = array_map("commandbar_serialize_post", $pages);
      ?>
        <script>
          <?php
          if (!$viewing_admin) { ?>
            const pages = <?php echo json_encode(array_values($pages)) ?>;
            pages.forEach(function(page, idx) {
              if (!page.permalink) return;
              window.CommandBar.addCommand({
                category: "Pages",
                text: page.title,
                explanation: page.content,
                name: `view_#{page.title}`,
                template: {
                  type: 'link',
                  value: page.permalink,
                  operation: 'self',
                },
                sort_key: idx+1
              });
            });
          <?php } 

          if ($viewing_admin) {
            global $wp_post_types;
            global $menu;
            global $submenu;
      
            $menu_by_parent_file = [];
            foreach ($menu as $item) {
              $menu_by_parent_file[$item[2]] = $item;
            }
      
            $urls_from_menu = [];
            foreach ($submenu as $parent => $items) {
              foreach ($items as $item) {
                if (!array_key_exists($parent, $menu_by_parent_file)) continue;
      
                $url = $item[2];
      
                $icons_by_url[$url] = commandbar_make_icon_string($menu_by_parent_file[$parent][6]);
              }
            }
      
            $editable_post_types = [];
            if (current_user_can('editor')) {
                global $wp_post_types;
      
                foreach ($wp_post_types as $post_type => $descriptor) {
                    if (current_user_can($descriptor->cap->edit_posts)) {
                        array_push($editable_post_types, $post_type);
                    }
                }
      
                $post_types = $editable_post_types;
            } else {
                $post_types = 'any';
                $post_status = NULL;
            }
            
            $index = 1;
            foreach ($editable_post_types as $post_type) {
              $index++;
              $descriptor = $wp_post_types[$post_type];
              if (!($descriptor->show_ui && $descriptor->show_in_menu)) continue;
              $add_new_item_url = add_query_arg(array('post_type' => $post_type), 'post-new.php');
              $edit_item_url = add_query_arg(array('post_type' => $post_type), 'edit.php');
      
              # see post-new.php#28
              if ('attachment' == $post_type) {
                $add_new_item_url = 'media-new.php';
              } else if ('post' == $post_type) {
                $add_new_item_url = 'post-new.php';
              }
      
              $icon = NULL;
              if (array_key_exists($add_new_item_url, $icons_by_url) && $icons_by_url[$add_new_item_url]) {
                $icon = $icons_by_url[$add_new_item_url];
              } else if (array_key_exists($edit_item_url, $icons_by_url) && $icons_by_url[$edit_item_url]) {
                $icon = $icons_by_url[$edit_item_url];
              }
      
      
              $label = "Add " . $descriptor->labels->singular_name;
              
              $command = [
                'category' => 'Create',
                'sort_key' => $index,
                'text' => $label,
                'name' => $label,
                'tags' => ["Create {$descriptor->labels->singular_name}"],
                'template' => [
                  'type' => 'link',
                  'value' => $add_new_item_url,
                  'operation' => 'self',
                ],
              ];

              if($icon) {
                $command['icon'] = $icon;
              }

              if($options['default_shortcuts'] && array_key_exists("post-type-$post_type", COMMANDBAR_PLUGIN_SHORTCUTS)) {
                $command['hotkey_win'] = COMMANDBAR_PLUGIN_SHORTCUTS["post-type-$post_type"];
                $command['hotkey_mac'] = COMMANDBAR_PLUGIN_SHORTCUTS["post-type-$post_type"];
              }
          ?>
              window.CommandBar.addCommand(<?php echo json_encode($command) ?>);
          <?php }
          ?>
          window.CommandBar.setCategoryConfig("Create", { sort_key: 1 });
          <?php
          } ?>
      
          window.CommandBar.addContext('viewing_admin', <?php echo json_encode(!!$viewing_admin) ?>);
      
          <?php
          if ($viewing_admin) {
            $index = 1;
            foreach ($menu as $item) {
              if (!commandbar_menu_url($item)) continue;
              
              $label = array_key_exists(0, $item) ? $item[0] : NULL;
              $name = array_key_exists(5, $item) ? $item[5] : NULL;
              $icon = array_key_exists(6, $item) ? $item[6] : NULL;
              
              // strip HTML tags and content from label
              $label = commandbar_only_text_nodes_in_root($label);

              $command = [
                'category' => 'Navigate',
                'sort_key' => $index,
                'text' => $label,
                'name' => "menu_$label",
                'template' => [
                  'type' => 'link',
                  'value' => commandbar_menu_url($item),
                  'operation' => 'self',
                ]
              ];
              
              // special case for Automattic Jetpack Plugin icon
              if($name == 'toplevel_page_jetpack') {
                $command['icon'] = '<span class="dashicons" style="font-family: jetpack !important;">&#xf100;</span>';
              } else if($icon) {
                $command['icon'] = commandbar_make_icon_string($icon);
              }

              if($options['default_shortcuts'] && array_key_exists($name, COMMANDBAR_PLUGIN_SHORTCUTS)) {
                $command['hotkey_win'] = COMMANDBAR_PLUGIN_SHORTCUTS[$name];
                $command['hotkey_mac'] = COMMANDBAR_PLUGIN_SHORTCUTS[$name];
              }
              
              ?>
              window.CommandBar.addCommand(<?php echo json_encode($command) ?>);
          <?php 
            $index++;
            }
            ?>
            window.CommandBar.setCategoryConfig("Navigate", { sort_key: 2 });
            <?php
          } ?>
      
      
          <?php if (wp_get_current_user()->id) {
            $serialized_user = $this->serialize_current_user();
          ?>
            window.CommandBar.addContext('current_user', <?php echo json_encode($serialized_user) ?>);
          <?php } ?>
        </script>
      <?php
    }
}

new CommandBar_Context();
