<?php

class CommandBar_Activation
{
    public function __construct()
    {
        register_activation_hook('commandbar-for-wp-admin/commandbar.php', [$this, 'plugin_activate']);

        if (get_option('commandbar_plugin_show_tos_acceptance_banner', TRUE)) {
            add_action('admin_notices', [$this, 'tos_acceptance_banner']);
        }

        if (get_option('commandbar_plugin_show_warning_message')) {
            add_action('admin_notices', [$this, 'warning_message']);
        }


        add_action('admin_notices', [$this, 'admin_notices']);
    }

    function plugin_activation_error($error = NULL)
    {
        $message = "<p>Error activating CommandBar plugin; please try again or contact us at <a href='mailto:support@commandbar.com'>support@commandbar.com</a>.</p>";

        if ($error) {
            $message = $message . "<pre>" . esc_html($error) . "</pre>";
        }

        die($message);
    }

    function plugin_activate()
    {
        $invite_sent = false;

        update_option('commandbar_plugin_show_tos_acceptance_banner', ["invite_sent" => $invite_sent]);
    }

    function tos_acceptance_banner()
    {
        $email = get_bloginfo('admin_email');
        $message = get_option('commandbar_plugin_show_tos_acceptance_banner', ["invite_sent" => false]);
?>
        <div class="notice notice-success">
            <p>Thanks for installing CommandBar! CommandBar enables fast navigation for your site. It searches your posts, pages, and more. Try it out on your site by pressing CMD-K or clicking on the search box.</p>
            <p>By using CommandBar, you agree to our <a href="https://www.commandbar.com/terms">Terms of Use</a>, including the collection of product usage data. All collected data is treated in accordance with our <a href="https://www.commandbar.com/privacy">Privacy Policy</a>.</p>
            
            <?php if($message['invite_sent']) { ?>
                <p>Check your email (<?php echo $email ?>) for an invitation to login to your Dashboard and Editor; these will help you customize your Bar.</p>
            <?php } ?>
            
            <p>
                <form method="post" action="<?php echo esc_attr(admin_url( 'admin-post.php' )) ?>">
                    <?php echo wp_nonce_field( 'commandbar_accept_tos' ) ?>
                    <input type="hidden" name="action" value="commandbar_accept_tos" />
                    <input name="submit" class="button button-primary" type="submit" value="Close and accept terms" />
                </form>
            </p>
        </div>
        <?php
    }

    function warning_message()
    {
        $message = get_option('commandbar_plugin_show_warning_message');
        if(!$message) return;

        update_option('commandbar_plugin_show_warning_message', FALSE);
?>
        <div class="notice notice-warning is-dismissible">
            <?php echo $message ?>
        </div>
        <?php
    }

    function admin_notices()
    {
        if (!get_option('commandbar_plugin_org_id') && !get_option('commandbar_plugin_show_tos_acceptance_banner')) {
        ?>
            <div class="notice notice-error">
                <p>There was an error with your CommandBar configuration; no organization ID was found. Please contact support@commandbar.com for assistance.</p>
            </div>
<?php
        }
    }
}

new CommandBar_Activation();
