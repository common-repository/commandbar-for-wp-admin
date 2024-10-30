<?php

class CommandBar_Override_Search
{
    function __construct()
    {
        $options = commandbar_get_options();
        $override_search = $options['override_search'];

        if (!$override_search) return;
        if (!COMMANDBAR_ENABLE_FOR_NON_ADMIN_USERS) return;

        add_action('wp_footer', [$this, 'override_search_script']);
        add_filter('get_product_search_form', [$this, 'wc_render_search']);
    }

    function override_search_script()
    {
        wp_enqueue_script("commandbar_override_core_search", plugin_dir_url(__FILE__) . 'js/override-core-search.js', [], COMMANDBAR_PLUGIN_VERSION, true);
    }
    function wc_render_search($form)
    {
        return '<div onClick="window.CommandBar.open(); return false;">' . $form . '</div>';
    }
}

new CommandBar_Override_Search();
