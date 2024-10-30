<?php

/*
      '<label for="commandbar_plugin_setting_override_search">Allow CommandBar to replace standard "search" widgets</label>' => 'setting_override_search',

        $options = commandbar_get_options();
        $value = $options['override_search'];
    ?>
        <div>
            <input type='checkbox' id='commandbar_plugin_setting_override_search' name='commandbar_plugin_options[override_search]' type='text' <?php echo (!!$value ? ' checked ' : '') ?> />
            <p class="description">
                When a user clicks on a "Search" widget, open the CommandBar instead. Defaults to "enabled."
            </p>
        </div>
<?php
*/

define('COMMANDBAR_PLUGIN_SETTINGS', [
    'override_search' => [
        /* only show this setting if we have CommandBar enabled for non-admins */
      'visible' => COMMANDBAR_ENABLE_FOR_NON_ADMIN_USERS,
      
      'default' => TRUE,
      'label' => 'Allow CommandBar to replace standard "search" widgets',
      'description' => 'When a user clicks on a "Search" widget, open the CommandBar instead. Defaults to "enabled."',
      'type' => 'checkbox'
    ],
    'default_shortcuts' => [
      'visible' => TRUE,
      'default' => TRUE,
      'label' => 'Default shortcuts',
      'description' => 'Enable the default shortcuts for navigation, post creation, etc.',
      'type' => 'checkbox'
    ]
]);

define('COMMANDBAR_PLUGIN_SHORTCUTS', [
    'menu-dashboard' => 'g d',
    'menu-posts' => 'g p',
    'post-type-post' => 'c'
]);


function commandbar_get_options() {
    $default_options = array_map(function($setting) { return $setting['default']; }, COMMANDBAR_PLUGIN_SETTINGS);

    $options = get_option('commandbar_plugin_options', $default_options);
    if(!$options) {
        $options = $default_options;
    }

    // merge in the default values from COMMANDBAR_PLUGIN_SETTINGS
    $options = array_merge($default_options, $options);
    
    return $options;
}

class CommandBar_Settings
{
    public function __construct()
    {
        add_action('admin_init', [$this, 'register_settings']);

        add_action('admin_menu', [$this, 'admin_menu']);

        add_action('admin_post_commandbar_invite_teammate', [$this, 'invite_teammate']);
        add_action('admin_post_commandbar_accept_tos', [$this, 'accept_tos']);
    }

    function admin_menu()
    {
        $icon = 'data:image/svg+xml;base64,PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiIHN0YW5kYWxvbmU9Im5vIj8+CjxzdmcKICAgd2lkdGg9IjI0cHgiCiAgIGhlaWdodD0iMjRweCIKICAgdmlld0JveD0iMCAwIDE3IDE2IgogICBmaWxsPSJub25lIgogICB2ZXJzaW9uPSIxLjEiCiAgIGlkPSJzdmc0MyIKICAgc29kaXBvZGk6ZG9jbmFtZT0iaWNvbl9ncmF5LnN2ZyIKICAgaW5rc2NhcGU6dmVyc2lvbj0iMS4xLjIgKGI4ZTI1YmU4LCAyMDIyLTAyLTA1KSIKICAgeG1sbnM6aW5rc2NhcGU9Imh0dHA6Ly93d3cuaW5rc2NhcGUub3JnL25hbWVzcGFjZXMvaW5rc2NhcGUiCiAgIHhtbG5zOnNvZGlwb2RpPSJodHRwOi8vc29kaXBvZGkuc291cmNlZm9yZ2UubmV0L0RURC9zb2RpcG9kaS0wLmR0ZCIKICAgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIgogICB4bWxuczpzdmc9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj4KICA8ZGVmcwogICAgIGlkPSJkZWZzNDciIC8+CiAgPHNvZGlwb2RpOm5hbWVkdmlldwogICAgIGlkPSJuYW1lZHZpZXc0NSIKICAgICBwYWdlY29sb3I9IiNmZmZmZmYiCiAgICAgYm9yZGVyY29sb3I9IiM2NjY2NjYiCiAgICAgYm9yZGVyb3BhY2l0eT0iMS4wIgogICAgIGlua3NjYXBlOnBhZ2VzaGFkb3c9IjIiCiAgICAgaW5rc2NhcGU6cGFnZW9wYWNpdHk9IjAuMCIKICAgICBpbmtzY2FwZTpwYWdlY2hlY2tlcmJvYXJkPSIwIgogICAgIHNob3dncmlkPSJmYWxzZSIKICAgICBpbmtzY2FwZTp6b29tPSIzNy4zNzUiCiAgICAgaW5rc2NhcGU6Y3g9IjYuNTU1MTgzOSIKICAgICBpbmtzY2FwZTpjeT0iMTIiCiAgICAgaW5rc2NhcGU6d2luZG93LXdpZHRoPSIyMTMzIgogICAgIGlua3NjYXBlOndpbmRvdy1oZWlnaHQ9IjEwODEiCiAgICAgaW5rc2NhcGU6d2luZG93LXg9IjYzIgogICAgIGlua3NjYXBlOndpbmRvdy15PSI2NiIKICAgICBpbmtzY2FwZTp3aW5kb3ctbWF4aW1pemVkPSIwIgogICAgIGlua3NjYXBlOmN1cnJlbnQtbGF5ZXI9InN2ZzQzIiAvPgogIDxwYXRoCiAgICAgaWQ9InJlY3QxNDcwIgogICAgIHN0eWxlPSJmaWxsOiM3MDZlNzg7ZmlsbC1vcGFjaXR5OjE7c3Ryb2tlLXdpZHRoOjAuOTU0Mzc0IgogICAgIGQ9Ik0gMi44NjMyODEyIDAuODk2NDg0MzggQyAxLjM5NTgyOTcgMC44OTY0ODQzOCAwLjIxNDg0Mzc1IDIuMDc3NDcwNCAwLjIxNDg0Mzc1IDMuNTQ0OTIxOSBMIDAuMjE0ODQzNzUgMjAuNDU1MDc4IEMgMC4yMTQ4NDM3NSAyMS45MjI1MyAxLjM5NTgyOTcgMjMuMTAzNTE2IDIuODYzMjgxMiAyMy4xMDM1MTYgTCAyMS4xMzY3MTkgMjMuMTAzNTE2IEMgMjIuNjA0MTcgMjMuMTAzNTE2IDIzLjc4NTE1NiAyMS45MjI1MyAyMy43ODUxNTYgMjAuNDU1MDc4IEwgMjMuNzg1MTU2IDMuNTQ0OTIxOSBDIDIzLjc4NTE1NiAyLjA3NzQ3MDQgMjIuNjA0MTcgMC44OTY0ODQzOCAyMS4xMzY3MTkgMC44OTY0ODQzOCBMIDIuODYzMjgxMiAwLjg5NjQ4NDM4IHogTSA5LjQxNzk2ODggNC42NjQwNjI1IEMgMTAuOTM1NzU2IDQuNjQwMTExNiAxMi40Mjk2NDIgNS4wODcyNjk4IDEzLjY4OTQ1MyA1Ljk1MzEyNSBDIDE1LjAwMTAzNCA2Ljg1NDU2NCAxNS45ODA1NjkgOC4xNTYwNDM3IDE2LjQ4NjMyOCA5LjY1NjI1IEMgMTYuNTY4NDkzIDkuODk5Nzg1OSAxNi40MjAwOTkgMTAuMTU0MTk1IDE2LjE3MTg3NSAxMC4yMjA3MDMgTCAxNC41NzYxNzIgMTAuNjQ4NDM4IEMgMTQuMjk2NjQ1IDEwLjcyMzM0NiAxNC4wMTE4MzUgMTAuNTU2NjE5IDEzLjkwNjI1IDEwLjI4NzEwOSBDIDEzLjU2NTI5OCA5LjQxNjgzNjMgMTIuOTcwMTgzIDguNjY0NzcxOCAxMi4xOTMzNTkgOC4xMzA4NTk0IEMgMTEuMjcxODg2IDcuNDk3NTU4NCAxMC4xNTQ5NTMgNy4yMTMyMTQ4IDkuMDQyOTY4OCA3LjMzMDA3ODEgQyA3LjkzMDk4NDcgNy40NDY5NTM3IDYuODk2NjEzMSA3Ljk1NjUyNjggNi4xMjY5NTMxIDguNzY3NTc4MSBDIDUuMzU3MjkzMiA5LjU3ODYyOTYgNC45MDIyNjEzIDEwLjYzNzMxOCA0Ljg0Mzc1IDExLjc1MzkwNiBDIDQuNzg1MjI2NiAxMi44NzA0ODMgNS4xMjc5MjU0IDEzLjk3MjMxMiA1LjgwODU5MzggMTQuODU5Mzc1IEMgNi40ODkyNjIxIDE1Ljc0NjQwMSA3LjQ2NDkxNzMgMTYuMzYxMzIxIDguNTU4NTkzOCAxNi41OTM3NSBDIDkuNjUyMjcwMSAxNi44MjYzMDIgMTAuNzkyNzczIDE2LjY2MDUwNCAxMS43NzUzOTEgMTYuMTI2OTUzIEMgMTIuNjAzNzgyIDE1LjY3NzE1OCAxMy4yNzMwNzEgMTQuOTkwMDMgMTMuNzAzMTI1IDE0LjE2MDE1NiBDIDEzLjgzNjMgMTMuOTAzMTYyIDE0LjEzNzk4MyAxMy43NjczNTggMTQuNDA4MjAzIDEzLjg3MTA5NCBMIDE1Ljg5NDUzMSAxNC40NDE0MDYgQyAxNS44OTg3NjcgMTQuNDQzMDMgMTUuOTA0MTA5IDE0LjQ0NTUyOSAxNS45MDgyMDMgMTQuNDQ3MjY2IEwgMjEuNDY2Nzk3IDE2LjU4MDA3OCBDIDIxLjczNjk0NCAxNi42ODM4IDIxLjg3MTMwMiAxNi45ODc2NjYgMjEuNzY3NTc4IDE3LjI1NzgxMiBMIDIxLjIwNTA3OCAxOC43MjY1NjIgQyAyMS4xMDEzNTYgMTguOTk2NzA5IDIwLjc5NzYxMyAxOS4xMzEwNjggMjAuNTI3MzQ0IDE5LjAyNzM0NCBMIDE2Ljk5ODA0NyAxNy42NzE4NzUgTCAxNi40OTYwOTQgMTcuNDgwNDY5IEMgMTYuMTMzMjQ1IDE3LjM0MTExMyAxNS43MzM1MzQgMTcuMTgzMDU4IDE1LjQ2MDkzOCAxNi44OTA2MjUgQyAxNS4zODYzNTQgMTYuODEwNjYzIDE1LjMwMjk0NiAxNi43MDQ3NDcgMTUuMjM2MzI4IDE2LjYxNzE4OCBDIDE0LjYzNDU2MSAxNy4zNjAyNzQgMTMuODg4NDU2IDE3Ljk4NDAwMSAxMy4wMzUxNTYgMTguNDQ3MjY2IEMgMTEuNDk5ODE2IDE5LjI4MDk3MyA5LjcxNjY5MDQgMTkuNTQwOTUgOC4wMDc4MTI1IDE5LjE3NzczNCBDIDYuMjk4OTQ3IDE4LjgxNDUxOCA0Ljc3NjQyOTYgMTcuODUyNzk3IDMuNzEyODkwNiAxNi40NjY3OTcgQyAyLjY0OTM1MjYgMTUuMDgwNjc0IDIuMTE1NTk3MiAxMy4zNTk4ODMgMi4yMDcwMzEyIDExLjYxNTIzNCBDIDIuMjk4NDY1MyA5Ljg3MDU3MzcgMy4wMDgzNDQxIDguMjE2NDgyIDQuMjEwOTM3NSA2Ljk0OTIxODggQyA1LjQxMzUyOTggNS42ODE5NTU2IDcuMDMwMTA1OSA0Ljg4NTc0OTMgOC43Njc1NzgxIDQuNzAzMTI1IEMgOC45ODQ3NjM3IDQuNjgwMjk4NSA5LjIwMTE0MiA0LjY2NzQ4NDEgOS40MTc5Njg4IDQuNjY0MDYyNSB6ICIKICAgICB0cmFuc2Zvcm09Im1hdHJpeCgwLjcwODMzMzMzLDAsMCwwLjcwODMzMzMzLDAsLTAuNSkiIC8+Cjwvc3ZnPgo=';
        add_menu_page('CommandBar', 'CommandBar', 'manage_options', 'commandbar', [$this, 'render_plugin_settings_page'], $icon, '59');
    }

    function register_settings()
    {
        $options = commandbar_get_options();
        $added_settings = FALSE;
        foreach (COMMANDBAR_PLUGIN_SETTINGS as $setting_name => $setting_config) {
            if(!$setting_config['visible']) continue;

            add_settings_field(
                "commandbar_plugin_$setting_name", 
                $setting_config['label'], 
                [$this, 'settings_field_callback'], 
                'commandbar_plugin', 
                'api_settings', [
                  'label_for' => "commandbar_plugin_setting_$setting_name",
                  'name' => $setting_name,
                  'type' => $setting_config['type'],
                  'description' => $setting_config['description'],
                  'value' => $options[$setting_name]
                ]
            );

            $added_settings = TRUE;
        }

        if($added_settings) {
            register_setting('commandbar_plugin_options', 'commandbar_plugin_options', [$this, 'plugin_options_validate']);
            add_settings_section('api_settings', 'Settings', [$this, 'plugin_section_text'], 'commandbar_plugin');
        }
    }

    public function accept_tos() {
        check_admin_referer("commandbar_accept_tos");
        
        if (!get_option('commandbar_plugin_org_id')) {
            $installation_id = bin2hex(random_bytes(8));
            $installation_secret = bin2hex(random_bytes(32));
            update_option('commandbar_plugin_installation_id', $installation_id);
            update_option('commandbar_plugin_installation_secret', $installation_secret);

            $name = get_bloginfo('name');
            if(empty($name)) {
                $name = "WordPress Site";
            }
            $email = get_bloginfo('admin_email');
            $organization_name = "$name ($installation_id)";
            $domain = get_home_url();

            $site_info = [
                "wp_name" => $name,
                "wp_description" => get_bloginfo('description'),
                "wp_home_url" => get_home_url(),
                "wp_site_url" => get_site_url(),
                "wp_admin_email" => $email,
                "installation_id" => $installation_id
            ];
            
            $response = wp_remote_post(COMMANDBAR_API_ENDPOINT . 'auth/new_organzation/', [
                'method' => 'POST',
                'headers' => ['Content-Type' => 'application/json; charset=utf-8'],
                'body' => json_encode([
                    'email' => $email,
                    'organization_name' => $organization_name,
                    'organization_installation_id' => $installation_id,
                    'organization_installation_secret' => $installation_secret,
                    'domain' => $domain,
                    'wp_site_info' => $site_info
                ]),
                'data_format' => 'body'
            ]);

            if (is_wp_error($response) || !(wp_remote_retrieve_response_code($response) >= 200 &&  wp_remote_retrieve_response_code($response) < 400)) {
                $response_body = wp_remote_retrieve_body($response);
                if( empty($response_body) ) {
                    $response_body = "Something went wrong; please try again later or contact us at support@commandbar.com";
                }
                wp_redirect( esc_url_raw( add_query_arg( array( 'accept_tos_error' => rawurlencode(mb_strimwidth($response_body, 0, 200)) ), admin_url('admin.php?page=commandbar' ) ) ) );
                return;
            }

            $response_json = json_decode(wp_remote_retrieve_body($response));
            if (!property_exists($response_json, 'id') || !$response_json->id) {
                wp_redirect( esc_url_raw( add_query_arg( array( 'accept_tos_error' => rawurlencode(mb_strimwidth("Something went wrong; please try again later or contact us at support@commandbar.com", 0, 200)) ), admin_url('admin.php?page=commandbar' ) ) ) );
                return;
            }

            $org_id = $response_json->id;

            $response = wp_remote_get(COMMANDBAR_API_ENDPOINT . 'organizations/' . $org_id . '/users/security/', [
                'headers' => ['Content-Type' => 'application/json; charset=utf-8', 'Authorization' => "Bearer commandbarplugin_{$installation_id}_{$installation_secret}"],
            ]);
            $response_json = json_decode(wp_remote_retrieve_body($response));
            if (!property_exists($response_json, 'end_user_secret') || !$response_json->end_user_secret) {
                wp_redirect( esc_url_raw( add_query_arg( array( 'accept_tos_error' => rawurlencode(mb_strimwidth("Something went wrong; please try again later or contact us at support@commandbar.com", 0, 200)) ), admin_url('admin.php?page=commandbar' ) ) ) );
                return;
            }

            $end_user_secret = $response_json->end_user_secret;

            update_option('commandbar_plugin_org_id', $org_id);
            update_option('commandbar_plugin_end_user_secret', $end_user_secret);

            if(COMMANDBAR_ENABLE_EDITOR_ACCESS) {
                $response = wp_remote_post(COMMANDBAR_API_ENDPOINT . 'organizations/invite_teammate/', [
                    'method' => 'POST',
                    'headers' => ['Content-Type' => 'application/json; charset=utf-8', 'Authorization' => "Bearer commandbarplugin_{$installation_id}_{$installation_secret}"],
                    'body' => json_encode([
                        'email' => $email,
                    ]),
                    'data_format' => 'body'
                ]);

                if (is_wp_error($response) || !(wp_remote_retrieve_response_code($response) >= 200 &&  wp_remote_retrieve_response_code($response) < 400)) {
                    $error = wp_remote_retrieve_body($response);
                    update_option('commandbar_plugin_show_warning_message', "Failed to send invitation email to {$email}." . "<pre>" . esc_html($error) . "</pre>");
                } else {
                    $invite_sent = true;
                }
            }
        }

        $installation_id = get_option('commandbar_plugin_installation_id');
        $installation_secret = get_option('commandbar_plugin_installation_secret');


        $response = wp_remote_post(COMMANDBAR_API_ENDPOINT . 'organizations/accept_tos/', [
            'method' => 'POST',
            'headers' => ['Content-Type' => 'application/json; charset=utf-8', 'Authorization' => "Bearer commandbarplugin_{$installation_id}_{$installation_secret}"],
            'data_format' => 'body'
        ]);

        if (is_wp_error($response) || !(wp_remote_retrieve_response_code($response) >= 200 &&  wp_remote_retrieve_response_code($response) < 400)) {
            $response_body = wp_remote_retrieve_body($response);
            if( empty($response_body) ) {
                $response_body = "Something went wrong; please try again later or contact us at support@commandbar.com";
            }
            wp_redirect( esc_url_raw( add_query_arg( array( 'accept_tos_error' => rawurlencode(mb_strimwidth($response_body, 0, 200)) ), admin_url('admin.php?page=commandbar' ) ) ) );
            return;
        } else {
            ## workaround for https://core.trac.wordpress.org/ticket/40007 -- setting the option to FALSE first doesn't work
            update_option('commandbar_plugin_show_tos_acceptance_banner', 0);
            update_option('commandbar_plugin_show_tos_acceptance_banner', FALSE);
            wp_redirect(wp_get_referer());
            return;
        }
    }

    public function accept_tos_error() {
        $message = isset( $_REQUEST['accept_tos_error'] ) ?  wp_kses($_REQUEST['accept_tos_error'], 'entities') : NULL;
        if(!$message) return;

?>
        <div class="notice notice-warning is-dismissible">
            <p>
                Error accepting Terms of Service: <pre><?php echo esc_html($message) ?></pre>
            </p>
        </div>
        <?php
    }
    

    public function invite_teammate() {
        check_admin_referer("commandbar_invite_teammate");
        $installation_id = get_option('commandbar_plugin_installation_id');
        $installation_secret = get_option('commandbar_plugin_installation_secret');

        $email = sanitize_email($_POST['commandbar_teammate_email']);
        add_settings_error("commandbar_teammate_email_error", '', "Failed to invite teammate");

        $response = wp_remote_post(COMMANDBAR_API_ENDPOINT . 'organizations/invite_teammate/', [
            'method' => 'POST',
            'headers' => ['Content-Type' => 'application/json; charset=utf-8', 'Authorization' => "Bearer commandbarplugin_{$installation_id}_{$installation_secret}"],
            'body' => json_encode([
                'email' => $email,
            ]),
            'data_format' => 'body'
        ]);

        if (is_wp_error($response) || !(wp_remote_retrieve_response_code($response) >= 200 &&  wp_remote_retrieve_response_code($response) < 400)) {
            $response_body = wp_remote_retrieve_body($response);
            if( empty($response_body) ) {
                $response_body = "Something went wrong; please try again later or contact us at support@commandbar.com";
            }
            wp_redirect( esc_url_raw( add_query_arg( array( 'invite_teammate_error' => rawurlencode(mb_strimwidth($response_body, 0, 200)) ), admin_url('admin.php?page=commandbar' ) ) ) );
            return;
        } else {
            wp_redirect( esc_url_raw( add_query_arg( array( 'invite_teammate_success' =>  rawurlencode($email) ), admin_url('admin.php?page=commandbar' ) ) ) );
            return;
        }
    }
    
    public function invite_teammate_error() {
        $message = isset( $_REQUEST['invite_teammate_error'] ) ?  wp_kses($_REQUEST['invite_teammate_error'], 'entities') : NULL;
        if(!$message) return;

?>
        <div class="notice notice-warning is-dismissible">
            <p>
                Error inviting teammate: <pre><?php echo esc_html($message) ?></pre>
            </p>
        </div>
        <?php
    }

    public function invite_teammate_success() {
        $email = isset( $_REQUEST['invite_teammate_success'] ) ?  sanitize_email($_REQUEST['invite_teammate_success']) : NULL;
        if(!$email) return;

?>
        <div class="notice notice-success is-dismissible">
            <p>
                Sent invitation email to <?php echo esc_html($email) ?>
            </p>
        </div>
        <?php
    }


    function render_plugin_settings_page()
    {
        $email = get_bloginfo('admin_email');
?>
        <style>
            dl.commandbar_settings_page {
                border: 1px solid gray;
                max-width: 400px;
                padding: 1em;
                background: #ebebfa;
                margin-right: 1em;
            }
            dl.commandbar_settings_page dt {
                font-weight: 800;
            }
        </style>
        <?php $this->accept_tos_error() ?>
        <div>
            <h2><img width="200" src="<?php echo plugin_dir_url(__FILE__) . 'assets/logo_wordmark.svg' ?>" /></h2>
            <div>
                <dl class="commandbar_settings_page">
                    <dt>Installation id:</dt> <dd><?php echo esc_html(get_option('commandbar_plugin_installation_id')) ?></dd>

                    <dt>Organization id:</dt> <dd><?php echo esc_html(get_option('commandbar_plugin_org_id')) ?></dd>

                    <dt>Site URL:</dt> <dd><?php echo esc_html(get_site_url()) ?></dd>

                    <dt>Admin email:</dt> <dd><?php echo esc_html(get_option('admin_email')) ?></dd>
                </dl>
            </div>

            <?php if (COMMANDBAR_ENABLE_EDITOR_ACCESS) { ?>
            <div style="margin-bottom: 4em;">
                <h2>CommandBar Editor Access</h2>
                <?php $this->invite_teammate_error(); $this->invite_teammate_success(); ?>
                <p>Invite teammates (or yourself) to create a CommandBar account. 
                    Use your account to login to the editor and to manage your site on <a href="https://app.commandbar.com">https://app.commandbar.com</a>.</p>
                <form method="post" action="<?php echo esc_attr(admin_url( 'admin-post.php' )) ?>">
                    <?php echo wp_nonce_field( 'commandbar_invite_teammate' ) ?>
                    <input type="hidden" name="action" value="commandbar_invite_teammate" />
                    <label for="commandbar_teammate_email">Email address to invite:</label> 
                    <input type="email" size="32" id="commandbar_teammate_email" name="commandbar_teammate_email" placeholder="e.g. <?php echo esc_attr($email) ?>" /> 
                    <input name="submit" class="button button-primary" type="submit" value="Invite teammate (or resend invitation)" />
                </form>
            </div>
            <?php } ?>
            
            <?php if (count(array_filter(COMMANDBAR_PLUGIN_SETTINGS, function ($setting) { return $setting['visible']; })) > 0) { ?>
            <div>
                <form action="options.php" method="post">
                    <?php
                    settings_fields('commandbar_plugin_options');
                    do_settings_sections('commandbar_plugin'); ?>
                    <input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e('Save'); ?>" />
                </form>
            </div>
            <?php } ?>
        </div>
    <?php
    }

    function plugin_options_validate($input)
    {
        $input = $input ? $input : [];
        
        $validated = [];

        foreach(COMMANDBAR_PLUGIN_SETTINGS as $setting_name => $setting_config) {
            if($setting_config['type'] == 'checkbox') {
                $validated[$setting_name] = array_key_exists($setting_name, $input) ? !!$input[$setting_name] : FALSE;
            } else {
                $validated[$setting_name] = $input[$setting_name];
            }
        }
        
        return $validated;
    }


    function plugin_section_text()
    { ?>
        <p>Adjust the configuration of your CommandBar</p>
    <?php
    }

    /**
     * @param args is an array with fields 'name', 'type', 'description', 'value'
     */
    function settings_field_callback($args)
    {
        $name = $args['name'];
        $value = $args['value'];
        $type = $args['type'];
        $description = $args['description'];

        if($type == 'checkbox') {
    ?>
        <div>
            <input type="checkbox" 
              id="commandbar_plugin_setting_<?php echo esc_attr($name) ?>" 
              name="commandbar_plugin_options[<?php echo esc_attr($name) ?>]"
              <?php echo (!!$value ? ' checked ' : '') ?> />

            <p class="description">
                <?php echo esc_html($description) ?>
            </p>
        </div>
<?php
        } else {
            error_log("unknown setting type $");
        }
    }
}

return new CommandBar_Settings();
