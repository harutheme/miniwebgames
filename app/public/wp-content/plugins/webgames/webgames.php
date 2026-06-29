<?php
/**
 * Plugin Name: Mini Web Games
 * Description: Core plugin for the Mini Web Games platform (CPT, Ads, Reports, Player Logic).
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: webgames
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

define( 'WEBGAMES_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WEBGAMES_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Load plugin textdomain
function webgames_load_textdomain() {
    load_plugin_textdomain( 'webgames', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'webgames_load_textdomain' );

// Include necessary files
require_once WEBGAMES_PLUGIN_DIR . 'includes/class-cpt-game.php';
require_once WEBGAMES_PLUGIN_DIR . 'includes/class-cpt-report.php';
require_once WEBGAMES_PLUGIN_DIR . 'includes/class-acf-fields.php';
require_once WEBGAMES_PLUGIN_DIR . 'includes/class-settings-page.php';
require_once WEBGAMES_PLUGIN_DIR . 'includes/class-ads-manager.php';
require_once WEBGAMES_PLUGIN_DIR . 'includes/class-ajax-handler.php';
require_once WEBGAMES_PLUGIN_DIR . 'includes/class-comment-spam.php';
require_once WEBGAMES_PLUGIN_DIR . 'includes/class-social-login.php';
require_once WEBGAMES_PLUGIN_DIR . 'includes/class-shortcodes.php';
require_once WEBGAMES_PLUGIN_DIR . 'includes/helpers.php';

// Disable automatic wpautop globally – removes auto‑generated <p> tags site‑wide
add_action( 'init', function() {
    remove_filter( 'the_content', 'wpautop' );
    remove_filter( 'the_excerpt', 'wpautop' );
} );

// Initialize classes
new Webgames_Social_Login();
new Webgames_CPT_Game();
new Webgames_CPT_Report();
new Webgames_ACF_Fields();
new Webgames_Settings_Page();
new Webgames_Ads_Manager();
new Webgames_Ajax_Handler();
new Webgames_Comment_Spam();
new Webgames_Shortcodes();
