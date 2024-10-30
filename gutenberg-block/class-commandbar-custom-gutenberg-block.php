<?php 
class CommandBar_CustomGutenbergBlock {
    public function __construct() {
        add_action( 'init', [$this, 'register'] );
        add_action( 'admin_enqueue_scripts', [$this, 'enqueue'] );
    }

    function register() {
        $asset_file = include( plugin_dir_path( __FILE__ ) . '/build/index.asset.php');
 
        wp_register_script(
            'commandbar-custom-gutenberg-block',
            plugins_url( '/build/index.js', __FILE__ ),
            $asset_file['dependencies'],
            $asset_file['version']
        );
    }
     
    function enqueue() {
        wp_enqueue_script( 'commandbar-custom-gutenberg-block' );
    }
};

new CommandBar_CustomGutenbergBlock();
