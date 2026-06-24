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
        $active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'ads_scripts';
        ?>
        <div class="wrap">
            <h1><?php _e( 'Webgames Global Settings', 'webgames' ); ?></h1>
            
            <h2 class="nav-tab-wrapper">
                <a href="?post_type=game&page=webgames-settings&tab=ads_scripts" class="nav-tab <?php echo $active_tab == 'ads_scripts' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Ads & Scripts', 'webgames' ); ?></a>
                <a href="?post_type=game&page=webgames-settings&tab=social_login" class="nav-tab <?php echo $active_tab == 'social_login' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Social Login', 'webgames' ); ?></a>
            </h2>

            <form method="post" action="options.php">
            <?php
                if ( $active_tab == 'ads_scripts' ) {
                    settings_fields( 'webgames_ads_group' );
                    do_settings_sections( 'webgames-ads-admin' );
                } else {
                    settings_fields( 'webgames_social_group' );
                    do_settings_sections( 'webgames-social-admin' );
                }
                submit_button();
            ?>
            </form>
        </div>
        <?php
    }

    public function page_init() {
        // --- Tab 1: Ads & Scripts ---
        register_setting( 'webgames_ads_group', 'webgames_global_scripts' );
        register_setting( 'webgames_ads_group', 'webgames_top_ad_code' );
        register_setting( 'webgames_ads_group', 'webgames_sidebar_ad_code' );
        register_setting( 'webgames_ads_group', 'webgames_sidebar_max_items', array(
            'type' => 'integer',
            'default' => 20,
            'sanitize_callback' => 'absint'
        ) );

        add_settings_section(
            'webgames_setting_section', // ID
            __( 'Ads & Scripts Configuration', 'webgames' ), // Title
            array( $this, 'print_section_info' ), // Callback
            'webgames-ads-admin' // Page
        );

        add_settings_field(
            'webgames_global_scripts', 
            __( 'Global Header Scripts (CMP / GA)', 'webgames' ), 
            array( $this, 'global_scripts_callback' ), 
            'webgames-ads-admin', 
            'webgames_setting_section'
        );

        add_settings_field(
            'webgames_top_ad_code', 
            __( 'Under Player Ad Code', 'webgames' ), 
            array( $this, 'top_ad_callback' ), 
            'webgames-ads-admin', 
            'webgames_setting_section'
        );

        add_settings_field(
            'webgames_sidebar_ad_code', 
            __( 'Sidebar Ad Code', 'webgames' ), 
            array( $this, 'sidebar_ad_callback' ), 
            'webgames-ads-admin', 
            'webgames_setting_section'
        );

        add_settings_field(
            'webgames_sidebar_max_items', 
            __( 'Sidebar Max Items', 'webgames' ), 
            array( $this, 'sidebar_max_items_callback' ), 
            'webgames-ads-admin', 
            'webgames_setting_section'
        );

        // --- Tab 2: Social Login ---
        register_setting( 'webgames_social_group', 'webgames_google_client_id' );
        register_setting( 'webgames_social_group', 'webgames_google_client_secret' );
        register_setting( 'webgames_social_group', 'webgames_facebook_app_id' );
        register_setting( 'webgames_social_group', 'webgames_facebook_app_secret' );

        add_settings_section(
            'webgames_social_login_section',
            __( 'Social Login Configuration', 'webgames' ),
            array( $this, 'print_social_section_info' ),
            'webgames-social-admin'
        );

        add_settings_field(
            'webgames_google_client_id', 
            __( 'Google Client ID', 'webgames' ), 
            array( $this, 'google_client_id_callback' ), 
            'webgames-social-admin', 
            'webgames_social_login_section'
        );
        add_settings_field(
            'webgames_google_client_secret', 
            __( 'Google Client Secret', 'webgames' ), 
            array( $this, 'google_client_secret_callback' ), 
            'webgames-social-admin', 
            'webgames_social_login_section'
        );
        add_settings_field(
            'webgames_facebook_app_id', 
            __( 'Facebook App ID', 'webgames' ), 
            array( $this, 'facebook_app_id_callback' ), 
            'webgames-social-admin', 
            'webgames_social_login_section'
        );
        add_settings_field(
            'webgames_facebook_app_secret', 
            __( 'Facebook App Secret', 'webgames' ), 
            array( $this, 'facebook_app_secret_callback' ), 
            'webgames-social-admin', 
            'webgames_social_login_section'
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

    public function print_social_section_info() {
        echo '<p>' . __( 'Enter your API keys for Google and Facebook login. Leave blank to disable a provider.', 'webgames' ) . '</p>';
        
        $callback_google = site_url( '?webgames_social_login=1&provider=google' );
        $callback_fb = site_url( '?webgames_social_login=1&provider=facebook' );

        echo '<div style="background: #fff; padding: 15px; border-left: 4px solid #00a0d2; margin-top: 15px; margin-bottom: 20px;">';
        echo '<p style="margin-top:0;"><strong>' . __( 'Setup Instructions & Callback URLs:', 'webgames' ) . '</strong></p>';
        
        echo '<p style="margin-bottom: 5px;"><strong>Google:</strong> <a href="https://console.cloud.google.com/apis/credentials" target="_blank">Get Keys at Google Cloud Console &rarr;</a></p>';
        echo '<p style="margin-top: 0; margin-bottom: 15px;">Authorized redirect URI: <code style="user-select: all;">' . esc_html( $callback_google ) . '</code></p>';
        
        echo '<p style="margin-bottom: 5px;"><strong>Facebook:</strong> <a href="https://developers.facebook.com/apps/" target="_blank">Get Keys at Meta for Developers &rarr;</a></p>';
        echo '<p style="margin-top: 0;">Valid OAuth Redirect URIs: <code style="user-select: all;">' . esc_html( $callback_fb ) . '</code></p>';
        echo '</div>';
    }

    public function google_client_id_callback() {
        $val = get_option( 'webgames_google_client_id' );
        echo '<input type="text" name="webgames_google_client_id" value="' . esc_attr( $val ) . '" style="width: 100%; max-width: 400px;" />';
    }

    public function google_client_secret_callback() {
        $val = get_option( 'webgames_google_client_secret' );
        echo '<input type="password" name="webgames_google_client_secret" value="' . esc_attr( $val ) . '" style="width: 100%; max-width: 400px;" />';
    }

    public function facebook_app_id_callback() {
        $val = get_option( 'webgames_facebook_app_id' );
        echo '<input type="text" name="webgames_facebook_app_id" value="' . esc_attr( $val ) . '" style="width: 100%; max-width: 400px;" />';
    }

    public function facebook_app_secret_callback() {
        $val = get_option( 'webgames_facebook_app_secret' );
        echo '<input type="password" name="webgames_facebook_app_secret" value="' . esc_attr( $val ) . '" style="width: 100%; max-width: 400px;" />';
    }
}
