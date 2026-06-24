<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Webgames_Settings_Page {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'page_init' ) );
    }

    public function add_plugin_page() {
        add_submenu_page(
            'edit.php?post_type=game', // Parent slug
            __( 'Webgames Settings', 'webgames' ), // Page title
            __( 'Settings', 'webgames' ), // Menu title
            'manage_options', // Capability
            'webgames-settings', // Menu slug
            array( $this, 'create_admin_page' ) // Function
        );
    }

    public function create_admin_page() {
        ?>
        <div class="wrap">
            <h1><?php _e( 'Webgames Global Settings', 'webgames' ); ?></h1>
            <form method="post" action="options.php">
            <?php
                settings_fields( 'webgames_option_group' );
                do_settings_sections( 'webgames-setting-admin' );
                submit_button();
            ?>
            </form>
        </div>
        <?php
    }

    public function page_init() {
        register_setting(
            'webgames_option_group', // Option group
            'webgames_global_scripts' // Option name
        );
        register_setting(
            'webgames_option_group', 
            'webgames_top_ad_code' 
        );
        register_setting(
            'webgames_option_group', 
            'webgames_sidebar_ad_code' 
        );

        add_settings_section(
            'webgames_setting_section', // ID
            __( 'Ads & Scripts Configuration', 'webgames' ), // Title
            array( $this, 'print_section_info' ), // Callback
            'webgames-setting-admin' // Page
        );

        add_settings_field(
            'webgames_global_scripts', 
            __( 'Global Header Scripts (CMP / GA)', 'webgames' ), 
            array( $this, 'global_scripts_callback' ), 
            'webgames-setting-admin', 
            'webgames_setting_section'
        );

        add_settings_field(
            'webgames_top_ad_code', 
            __( 'Under Player Ad Code', 'webgames' ), 
            array( $this, 'top_ad_callback' ), 
            'webgames-setting-admin', 
            'webgames_setting_section'
        );

        add_settings_field(
            'webgames_sidebar_ad_code', 
            __( 'Sidebar Ad Code', 'webgames' ), 
            array( $this, 'sidebar_ad_callback' ), 
            'webgames-setting-admin', 
            'webgames_setting_section'
        );

        register_setting(
            'webgames_option_group', 
            'webgames_sidebar_max_items',
            array(
                'type' => 'integer',
                'default' => 20,
                'sanitize_callback' => 'absint'
            )
        );

        add_settings_field(
            'webgames_sidebar_max_items', 
            __( 'Sidebar Max Items', 'webgames' ), 
            array( $this, 'sidebar_max_items_callback' ), 
            'webgames-setting-admin', 
            'webgames_setting_section'
        );
    }

    public function print_section_info() {
        print __( 'Enter your global tracking scripts and ad slot codes below.', 'webgames' );
    }

    public function global_scripts_callback() {
        $val = get_option( 'webgames_global_scripts' );
        echo '<textarea name="webgames_global_scripts" rows="5" style="width: 100%; font-family: monospace;">' . esc_textarea( $val ) . '</textarea>';
        echo '<p class="description">' . __( 'Outputs inside the <head> tag. Good for CMP, Google Analytics.', 'webgames' ) . '</p>';
    }

    public function top_ad_callback() {
        $val = get_option( 'webgames_top_ad_code' );
        echo '<textarea name="webgames_top_ad_code" rows="5" style="width: 100%; font-family: monospace;">' . esc_textarea( $val ) . '</textarea>';
        echo '<p class="description">' . __( 'Code for [webgames_under_player_ad] shortcode.', 'webgames' ) . '</p>';
    }

    public function sidebar_ad_callback() {
        $val = get_option( 'webgames_sidebar_ad_code' );
        echo '<textarea name="webgames_sidebar_ad_code" rows="5" style="width: 100%; font-family: monospace;">' . esc_textarea( $val ) . '</textarea>';
        echo '<p class="description">' . __( 'Code for interleaved ads in sidebar list.', 'webgames' ) . '</p>';
    }

    public function sidebar_max_items_callback() {
        $val = get_option( 'webgames_sidebar_max_items', 20 );
        echo '<input type="number" name="webgames_sidebar_max_items" value="' . esc_attr( $val ) . '" style="width: 100px;" min="1" max="100" />';
        echo '<p class="description">' . __( 'Maximum number of games to display in the sidebar. Default is 20.', 'webgames' ) . '</p>';
    }
}
