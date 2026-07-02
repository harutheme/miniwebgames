<?php
/**
 * Module 2: Single URL Scraper
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Webgames_Single_Scraper {

    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'add_scraper_meta_box' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'wp_ajax_webgames_scrape_url', array( $this, 'handle_scrape_ajax' ) );
        add_action( 'save_post_game', array( $this, 'save_scraper_meta' ) );
    }

    public function add_scraper_meta_box() {
        add_meta_box(
            'webgames_scraper_mb',
            __( 'Game Scraper (Auto Fill)', 'webgames-scrapper' ),
            array( $this, 'render_meta_box' ),
            'game',
            'side',
            'high'
        );
    }

    public function render_meta_box( $post ) {
        ?>
        <div class="wg-scraper-wrap">
            <label for="wg-scraper-source"><?php esc_html_e( 'Game Source:', 'webgames-scrapper' ); ?></label>
            <select id="wg-scraper-source" class="widefat" style="margin-bottom:12px;">
                <option value="musicgames">musicgames.io</option>
                <option value="generic"><?php esc_html_e( 'Auto Detect (Generic)', 'webgames-scrapper' ); ?></option>
            </select>

            <label for="wg-scraper-url"><?php esc_html_e( 'Target URL:', 'webgames-scrapper' ); ?></label>
            <input type="text" id="wg-scraper-url" placeholder="https://" class="widefat" style="margin-bottom:12px;"/>
            <button type="button" id="wg-btn-fetch" class="button button-primary wg-btn-block">
                <?php esc_html_e( 'Fetch Game Data', 'webgames-scrapper' ); ?>
            </button>
            <div id="wg-scraper-status" class="wg-status-msg"></div>

            <?php $existing_source = get_post_meta( $post->ID, '_wg_scraped_source_url', true ); ?>
            <?php if ( $existing_source ) : ?>
                <p class="description" style="margin-top:10px; font-size:12px; color:#666;">
                    <?php printf( __( 'Previously scraped from: <a href="%s" target="_blank" style="text-decoration:none;">Link</a>', 'webgames-scrapper' ), esc_url( $existing_source ) ); ?>
                </p>
            <?php endif; ?>
            <input type="hidden" id="wg_scraped_source_url" name="wg_scraped_source_url" value="<?php echo esc_attr( $existing_source ); ?>" />
        </div>
        <?php
    }

    public function save_scraper_meta( $post_id ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;
        
        if ( isset( $_POST['wg_scraped_source_url'] ) && ! empty( $_POST['wg_scraped_source_url'] ) ) {
            update_post_meta( $post_id, '_wg_scraped_source_url', esc_url_raw( $_POST['wg_scraped_source_url'] ) );
        }
    }

    public function enqueue_assets( $hook ) {
        global $post_type;
        if ( ( 'post.php' === $hook || 'post-new.php' === $hook ) && 'game' === $post_type ) {
            wp_enqueue_style( 'wg-scraper-admin-css', WEBGAMES_SCRAPPER_URL . 'assets/css/scraper-admin.css', array(), WEBGAMES_SCRAPPER_VERSION );
            wp_enqueue_script( 'wg-scraper-admin-js', WEBGAMES_SCRAPPER_URL . 'assets/js/scraper-admin.js', array( 'jquery' ), WEBGAMES_SCRAPPER_VERSION, true );
            
            wp_localize_script( 'wg-scraper-admin-js', 'wgScraperAjax', array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'wg_scraper_nonce' ),
                'fetching' => __( 'Fetching data...', 'webgames-scrapper' ),
                'success'  => __( 'Success! Data filled.', 'webgames-scrapper' ),
                'error'    => __( 'Error scraping URL.', 'webgames-scrapper' ),
            ) );
        }
    }

    public function handle_scrape_ajax() {
        check_ajax_referer( 'wg_scraper_nonce', 'security' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( __( 'Permission denied.', 'webgames-scrapper' ) );
        }

        $url = isset( $_POST['url'] ) ? esc_url_raw( $_POST['url'] ) : '';
        $source = isset( $_POST['source'] ) ? sanitize_text_field( $_POST['source'] ) : 'musicgames';
        
        if ( empty( $url ) ) {
            wp_send_json_error( __( 'Invalid URL.', 'webgames-scrapper' ) );
        }

        $response = wp_remote_get( $url );
        if ( is_wp_error( $response ) ) {
            wp_send_json_error( $response->get_error_message() );
        }

        $html = wp_remote_retrieve_body( $response );
        if ( empty( $html ) ) {
            wp_send_json_error( __( 'Empty response from target.', 'webgames-scrapper' ) );
        }

        libxml_use_internal_errors( true );
        $dom = new DOMDocument();
        $dom->loadHTML( mb_convert_encoding( $html, 'HTML-ENTITIES', 'UTF-8' ) );
        $xpath = new DOMXPath( $dom );

        // Strategy Pattern routing
        $parser = null;
        switch ( $source ) {
            case 'musicgames':
                $parser = new Webgames_Parser_Musicgames();
                break;
            case 'generic':
            default:
                $parser = new Webgames_Parser_Generic();
                break;
        }

        $parser->set_dom( $dom, $xpath, $html );

        $data = array(
            'title'       => $parser->get_title(),
            'description' => $parser->get_description(),
            'image_url'   => $parser->get_image_url(),
            'image_id'    => '',
            'iframe_url'  => $parser->get_iframe_url(),
            'source_url'  => $url, // Pass back to JS for tracking
        );

        // Duplicate Check based on Iframe URL
        if ( ! empty( $data['iframe_url'] ) ) {
            $existing_games = get_posts( array(
                'post_type'  => 'game',
                'meta_key'   => 'iframe_url',
                'meta_value' => $data['iframe_url'],
                'fields'     => 'ids',
                'numberposts'=> 1,
            ) );

            if ( ! empty( $existing_games ) ) {
                $existing_id = $existing_games[0];
                $edit_url = get_edit_post_link( $existing_id, 'raw' );
                $error_msg = sprintf(
                    __( 'Duplicate! This game already exists in the system. <a href="%s" target="_blank" style="text-decoration:underline;">Click here to edit it</a>.', 'webgames-scrapper' ),
                    esc_url( $edit_url )
                );
                wp_send_json_error( $error_msg );
            }
        }

        // Sideload image if available
        if ( ! empty( $data['image_url'] ) ) {
            require_once( ABSPATH . 'wp-admin/includes/media.php' );
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
            require_once( ABSPATH . 'wp-admin/includes/image.php' );
            
            $image_id = media_sideload_image( $data['image_url'], 0, $data['title'], 'id' );
            if ( ! is_wp_error( $image_id ) ) {
                $data['image_id'] = $image_id;
            }
        }

        wp_send_json_success( $data );
    }
}
