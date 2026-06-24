<?php
/**
 * Module 1: Bulk API Importer (Placeholder)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Webgames_API_Importer {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_api_menu' ) );
    }

    public function add_api_menu() {
        add_submenu_page(
            'edit.php?post_type=game',
            __( 'API Auto Importer', 'webgames-scrapper' ),
            __( 'API Auto Importer', 'webgames-scrapper' ),
            'manage_options',
            'webgames-api-importer',
            array( $this, 'render_admin_page' )
        );
    }

    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'API Auto Importer (Module 1)', 'webgames-scrapper' ); ?></h1>
            <div class="notice notice-info">
                <p><strong><?php esc_html_e( 'Coming Soon!', 'webgames-scrapper' ); ?></strong></p>
                <p><?php esc_html_e( 'This module will connect to bulk HTML5 game distributors (like GameDistribution or GamePix) via API to auto-fetch and publish thousands of games via WP Cron.', 'webgames-scrapper' ); ?></p>
            </div>
        </div>
        <?php
    }
}
