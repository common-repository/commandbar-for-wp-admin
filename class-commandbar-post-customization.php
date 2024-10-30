<?php
// https://wordpress.stackexchange.com/questions/61041/add-a-checkbox-to-post-screen-that-adds-a-class-to-the-title

class CommandBar_PostCustomization {
    public function __construct() {
        if(!COMMANDBAR_ENABLE_FOR_NON_ADMIN_USERS) return;
        
        /* Define the custom box */
        add_action('add_meta_boxes', [$this, 'add_page_settings_box']);

        /* Do something with the data entered */
        add_action('save_post', [$this, 'save_page_settings']);
    }

    /* Adds a box to the main column on the Post and Page edit screens */
    function add_page_settings_box()
    {
        add_meta_box(
            'commandbar_page_settings',
            'CommandBar',
            [$this, 'page_settings'],
            'page',
            'side',
            'high'
        );
    }

    /* Prints the box content */
    function page_settings($post)
    {
        // Use nonce for verification
        wp_nonce_field('commandbar_page_settings_field_nonce', 'commandbar_page_settings_noncename');

        // Get saved value, if none exists, "default" is selected
        $omit = get_post_meta($post->ID, 'commandbar_omit', true);
        ?>
        <div>
            <input type="checkbox" id="commandbar_show" name="commandbar_show" <?php echo $omit == 'true' ? '' : 'checked' ?> value="true" />
            <label for="commandbar_show">Show page in the Bar</label>
        </div>
        <?php
    }

    /* When the post is saved, saves our custom data */
    function save_page_settings($post_id)
    {
        // verify if this is an auto save routine. 
        // If it is our form has not been submitted, so we dont want to do anything
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            return;

        if (!array_key_exists('commandbar_page_settings_noncename', $_POST))
            return;

        // verify this came from the our screen and with proper authorization,
        // because save_post can be triggered at other times
        if (!wp_verify_nonce($_POST['commandbar_page_settings_noncename'], 'commandbar_page_settings_field_nonce'))
            return;

        $commandbar_omit = !(isset($_POST['commandbar_show']) && $_POST['commandbar_show'] != "");
        update_post_meta($post_id, 'commandbar_omit', $commandbar_omit ? 'true' : '');
    }
}

new CommandBar_PostCustomization();