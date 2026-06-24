<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Webgames_Ads_Manager {

    public function __construct() {
        add_action( 'wp_head', array( $this, 'output_global_scripts' ), 1 ); // Priority 1 to load early
        add_shortcode( 'webgames_under_player_ad', array( $this, 'render_under_player_ad' ) );
        add_shortcode( 'webgames_sidebar_ad', array( $this, 'render_sidebar_ad' ) );
    }

    public function output_global_scripts() {
        $scripts = get_option( 'webgames_global_scripts' );
        if ( ! empty( $scripts ) ) {
            // Output exactly as entered, allowing HTML/JS tags
            echo $scripts . "\n";
        }
    }

    public function render_under_player_ad() {
        $code = get_option( 'webgames_top_ad_code' );
        if ( empty( $code ) ) {
            return '';
        }
        
        // Wrap in a container with a class for styling/min-height
        ob_start();
        ?>
        <div class="webgames-ad-container webgames-under-player-ad">
            <?php echo do_shortcode( $code ); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_sidebar_ad() {
        $code = get_option( 'webgames_sidebar_ad_code' );
        if ( empty( $code ) ) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="webgames-ad-container webgames-sidebar-ad">
            <?php echo do_shortcode( $code ); ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
