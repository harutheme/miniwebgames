<?php
/**
 * Plugin Name: Webgames Scraper
 * Plugin URI: https://example.com/
 * Description: Auto Game Scraper with Single URL (musicgames.io) parsing and HTML5 Downloader.
 * Version: 1.0.0
 * Author: Antigravity
 * Text Domain: webgames-scrapper
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'WEBGAMES_SCRAPPER_VERSION', '1.0.0' );
define( 'WEBGAMES_SCRAPPER_DIR', plugin_dir_path( __FILE__ ) );
define( 'WEBGAMES_SCRAPPER_URL', plugin_dir_url( __FILE__ ) );

// Load Textdomain
add_action( 'plugins_loaded', 'webgames_scrapper_load_textdomain' );
function webgames_scrapper_load_textdomain() {
    load_plugin_textdomain( 'webgames-scrapper', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

// Include Core Files
require_once WEBGAMES_SCRAPPER_DIR . 'includes/class-source-registry.php';
require_once WEBGAMES_SCRAPPER_DIR . 'includes/parsers/interface-parser.php';
require_once WEBGAMES_SCRAPPER_DIR . 'includes/parsers/class-parser-musicgames.php';
require_once WEBGAMES_SCRAPPER_DIR . 'includes/parsers/class-parser-sprunkia.php';
require_once WEBGAMES_SCRAPPER_DIR . 'includes/parsers/class-parser-generic.php';

require_once WEBGAMES_SCRAPPER_DIR . 'includes/class-api-importer.php';
require_once WEBGAMES_SCRAPPER_DIR . 'includes/class-single-scraper.php';
require_once WEBGAMES_SCRAPPER_DIR . 'includes/class-html5-downloader.php';

// Initialize modules
function webgames_scrapper_init() {
    if ( is_admin() ) {
        new Webgames_API_Importer();
        new Webgames_Single_Scraper();
        new Webgames_HTML5_Downloader();
    }
}
add_action( 'plugins_loaded', 'webgames_scrapper_init' );
