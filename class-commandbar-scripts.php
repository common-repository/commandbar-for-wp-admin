<?php

class CommandBar_Scripts {
    public function __construct() {
        if(COMMANDBAR_ENABLE_FOR_NON_ADMIN_USERS) {
            add_action('wp_head', [$this, 'constants']);
            add_action('wp_enqueue_scripts', [$this, 'scripts']);
          }
          
          if(COMMANDBAR_ENABLE_FOR_ADMIN_USERS) {
            add_action('admin_print_scripts', [$this, 'constants']);
            add_action('admin_enqueue_scripts', [$this, 'scripts']);
            add_action('admin_enqueue_scripts', [$this, 'admin_only_scripts']);
            add_action('wp_before_admin_bar_render', [$this, 'wp_admin_bar_launcher']);
          }
    }

    function wp_admin_bar_launcher()
    {
        global $wp_admin_bar;

        if(!is_admin()) return;
        if(!get_option('commandbar_plugin_org_id')) return;

        $launcher_stylesheet_url = esc_attr(plugin_dir_url(__FILE__) . 'assets/commandbar-launcher.css');
        $os_control_key_url = esc_attr(plugin_dir_url(__FILE__) . 'assets/os-control-key.js');

        // NOTE: the <script>0</script> tag prevents a "flash of unstyled content" when the Launcher is first rendered
        $launcher_html = <<<EOF
        <html><head>
        <link rel="stylesheet" href="${launcher_stylesheet_url}" />
        <script src="${os_control_key_url}"></script>

        </head><body style="margin: 0; overflow: hidden;"><script>0</script>
        <div onclick="parent.window.CommandBar.open()" id="commandbar-user-launcher-component" class="commandbar-user-launcher"><div class="commandbar-user-launcher__content"><div class="commandbar-user-launcher__prefix"><svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 1024 1024" height="1em" width="1em" xmlns="http://www.w3.org/2000/svg"><path d="M909.6 854.5L649.9 594.8C690.2 542.7 712 479 712 412c0-80.2-31.3-155.4-87.9-212.1-56.6-56.7-132-87.9-212.1-87.9s-155.5 31.3-212.1 87.9C143.2 256.5 112 331.8 112 412c0 80.1 31.3 155.5 87.9 212.1C256.5 680.8 331.8 712 412 712c67 0 130.6-21.8 182.7-62l259.7 259.6a8.2 8.2 0 0 0 11.6 0l43.6-43.5a8.2 8.2 0 0 0 0-11.6zM570.4 570.4C528 612.7 471.8 636 412 636s-116-23.3-158.4-65.6C211.3 528 188 471.8 188 412s23.3-116.1 65.6-158.4C296 211.3 352.2 188 412 188s116.1 23.2 158.4 65.6S636 352.2 636 412s-23.3 116.1-65.6 158.4z"></path></svg>&nbsp; Find anything</div><div class="commandbar-user-launcher__suffix">
        
        <span id="commandbar-summon-hotkey" class="commandbar-user-launcher__tag" style="margin-right: 3px;"></span>
        
        </div></div></div>
        <script>document.getElementById("commandbar-summon-hotkey").innerText = osControlKey('K');</script>

        </body></html>
        EOF;
        $launcher = '<iframe style="padding-top: 1px; height: 32px; width: 258px;" srcdoc="' . esc_attr($launcher_html) . '"></iframe>';
        $wp_admin_bar->add_node([
            'id' => 'commandbar-launcher',
            'title' => $launcher
        ]);
    }
    
    function scripts()
    {
        wp_enqueue_script("commandbar_add_commands", plugin_dir_url(__FILE__) . 'js/add-commands.js', [], COMMANDBAR_PLUGIN_VERSION, true);
        
        wp_enqueue_script( 'wp-api' );
    }
    
    function admin_only_scripts()
    {
        wp_enqueue_script("commandbar_add_commands_admin", plugin_dir_url(__FILE__) . 'js/add-commands-admin.js', [], COMMANDBAR_PLUGIN_VERSION, true);
        wp_enqueue_script("commandbar_edit_form_fields_commands", plugin_dir_url(__FILE__) . 'js/edit-form-fields-commands.js', [], COMMANDBAR_PLUGIN_VERSION, true);
        wp_enqueue_script("commandbar_add_admin_context", plugin_dir_url(__FILE__) . 'js/add-admin-context.js', [], COMMANDBAR_PLUGIN_VERSION, true);
    }
    
    function get_postmeta_keys_by_post_type($post_type) {
      global $wpdb;

      $postmeta_keys_by_post_type = wp_cache_get( 'postmeta_keys_by_post_type', 'commandbar' );

      if(!$postmeta_keys_by_post_type) {
        $postmeta_keys_by_post_type = [];

        $postmeta_keys = $wpdb->get_results(
            "SELECT p.post_type, m.meta_key FROM {$wpdb->prefix}postmeta m " .
            "JOIN {$wpdb->prefix}posts p on p.id = m.post_id " .
            "WHERE meta_key NOT LIKE 'commandbar_%' " .
            "GROUP BY p.post_Type, m.meta_key;"
        );

        foreach($postmeta_keys as $row) {
          if(!isset($postmeta_keys_by_post_type[$row->post_type])) {
            $postmeta_keys_by_post_type[$row->post_type] = [];
          }

          array_push($postmeta_keys_by_post_type[$row->post_type], $row->meta_key);
        }

        wp_cache_set( 'postmeta_keys_by_post_type', $postmeta_keys_by_post_type, 'commandbar', 60*60 );
      }

      if(isset($postmeta_keys_by_post_type[$post_type])) {
        return $postmeta_keys_by_post_type[$post_type];
      } else {
        return NULL;
      }
    }

    function constants()
    {
      $post_types = [];
      foreach (get_post_types([], 'objects') as $post_type) {
        $allowed = true;
        if(COMMANDBAR_ENABLE_FOR_NON_ADMIN_USERS) {
          $allowed = $allowed && $post_type->public && $post_type->publicly_queryable;
        }
        if($allowed) {
          array_push($post_types, [
            'major_category' => !$post_type->exclude_from_search,
            'label' => $post_type->label,
            'name' => $post_type->name,
            'postmeta_keys' => $this->get_postmeta_keys_by_post_type($post_type->name)
          ]);
        }
      }
      // sort post_types by label
      usort($post_types, function($a, $b) {
       return strcmp($a['label'], $b['label']);
      });

      $options = commandbar_get_options();
      ?>
      <script type="application/javascript">


        window.CommandBarWPPlugin = {};
        window.CommandBarWPPlugin.POST_TYPES = <?php echo json_encode($post_types) ?>;
        window.CommandBarWPPlugin.OPTIONS = {
          DEFAULT_SHORTCUTS: <?php echo json_encode($options['default_shortcuts']) ?>
        };
      </script>
      <?php
    }
}

new CommandBar_Scripts();
