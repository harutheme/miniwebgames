<?php
/**
 * Module 3: HTML5 Source Downloader
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Webgames_HTML5_Downloader {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_downloader_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'wp_ajax_webgames_download_source', array( $this, 'handle_download_ajax' ) );
    }

    public function add_downloader_menu() {
        add_submenu_page(
            'edit.php?post_type=game',
            __( 'Source Downloader', 'webgames-scrapper' ),
            __( 'Source Downloader', 'webgames-scrapper' ),
            'manage_options',
            'webgames-source-downloader',
            array( $this, 'render_admin_page' )
        );
    }

    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'HTML5 Source Downloader (Localhost)', 'webgames-scrapper' ); ?></h1>
            <p><?php esc_html_e( 'Enter the direct iframe URL of a game to package its index.html and assets into a ZIP file for local downloading.', 'webgames-scrapper' ); ?></p>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="wg-download-source"><?php esc_html_e( 'Game Source:', 'webgames-scrapper' ); ?></label></th>
                    <td>
                        <select id="wg-download-source" class="regular-text">
                            <option value="musicgames">musicgames.io</option>
                            <option value="generic"><?php esc_html_e( 'Auto Detect (Generic)', 'webgames-scrapper' ); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="wg-download-url"><?php esc_html_e( 'Iframe URL:', 'webgames-scrapper' ); ?></label></th>
                    <td>
                        <input type="text" id="wg-download-url" class="regular-text" placeholder="https://..." />
                        <button type="button" id="wg-btn-download" class="button button-primary">
                            <?php esc_html_e( 'Download to Local', 'webgames-scrapper' ); ?>
                        </button>
                        <span class="spinner" id="wg-download-spinner"></span>
                        <p class="description"><?php esc_html_e( 'Note: Complex games with obfuscated assets or dynamic AJAX loading may not download completely.', 'webgames-scrapper' ); ?></p>
                    </td>
                </tr>
            </table>
            <div id="wg-download-status" style="margin-top: 20px; font-weight: bold;"></div>
        </div>
        <?php
    }

    public function enqueue_assets( $hook ) {
        if ( 'game_page_webgames-source-downloader' === $hook ) {
            wp_enqueue_script( 'wg-downloader-admin-js', WEBGAMES_SCRAPPER_URL . 'assets/js/downloader-admin.js', array( 'jquery' ), WEBGAMES_SCRAPPER_VERSION, true );
            
            wp_localize_script( 'wg-downloader-admin-js', 'wgDownloaderAjax', array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'wg_downloader_nonce' ),
                'packing'  => __( 'Scraping and packing ZIP... this may take a minute.', 'webgames-scrapper' ),
                'success'  => __( 'ZIP created! Downloading...', 'webgames-scrapper' ),
                'error'    => __( 'Error generating ZIP.', 'webgames-scrapper' ),
            ) );
        }
    }

    public function handle_download_ajax() {
        check_ajax_referer( 'wg_downloader_nonce', 'security' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( __( 'Permission denied.', 'webgames-scrapper' ) );
        }

        $url = isset( $_POST['url'] ) ? esc_url_raw( $_POST['url'] ) : '';
        $source = isset( $_POST['source'] ) ? sanitize_text_field( $_POST['source'] ) : 'musicgames';
        
        if ( empty( $url ) ) {
            wp_send_json_error( __( 'Invalid URL.', 'webgames-scrapper' ) );
        }

        if ( ! class_exists( 'ZipArchive' ) ) {
            wp_send_json_error( __( 'ZipArchive is not installed on this server.', 'webgames-scrapper' ) );
        }

        // Fetch index HTML
        $response = wp_remote_get( $url, array( 'timeout' => 15 ) );
        if ( is_wp_error( $response ) ) {
            wp_send_json_error( $response->get_error_message() );
        }

        $html = wp_remote_retrieve_body( $response );
        if ( empty( $html ) ) {
            wp_send_json_error( __( 'Empty response from URL.', 'webgames-scrapper' ) );
        }

        $parsed_url = parse_url( $url );
        $base_url = $parsed_url['scheme'] . '://' . $parsed_url['host'];
        $path_parts = pathinfo($parsed_url['path']);
        $base_path = $base_url . (isset($path_parts['dirname']) && $path_parts['dirname'] !== '/' ? $path_parts['dirname'] . '/' : '/');

        // Create temp dir
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/temp_games';
        if ( ! file_exists( $temp_dir ) ) {
            wp_mkdir_p( $temp_dir );
        }

        // Cleanup old zips
        $old_zips = glob( $temp_dir . '/*.zip' );
        foreach ( $old_zips as $old_zip ) {
            if ( filemtime( $old_zip ) < time() - 3600 ) { // older than 1 hour
                @unlink( $old_zip );
            }
        }

        $zip_name = 'game_source_' . time() . '.zip';
        $zip_path = $temp_dir . '/' . $zip_name;
        $zip_url  = $upload_dir['baseurl'] . '/temp_games/' . $zip_name;

        $zip = new ZipArchive();
        if ( $zip->open( $zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE ) !== true ) {
            wp_send_json_error( __( 'Could not create ZIP file.', 'webgames-scrapper' ) );
        }

        $zip->addFromString( 'index.html', $html );

        // Very basic asset scraping
        libxml_use_internal_errors( true );
        $dom = new DOMDocument();
        $dom->loadHTML( mb_convert_encoding( $html, 'HTML-ENTITIES', 'UTF-8' ) );
        
        $assets = array();
        
        // Scripts
        foreach ( $dom->getElementsByTagName('script') as $node ) {
            if ( $node->hasAttribute('src') ) $assets[] = $node->getAttribute('src');
        }
        // Links
        foreach ( $dom->getElementsByTagName('link') as $node ) {
            if ( $node->hasAttribute('href') ) $assets[] = $node->getAttribute('href');
        }
        // Images
        foreach ( $dom->getElementsByTagName('img') as $node ) {
            if ( $node->hasAttribute('src') ) $assets[] = $node->getAttribute('src');
        }

        $downloaded = array();
        foreach ( $assets as $asset ) {
            if ( empty( $asset ) || strpos( $asset, 'data:' ) === 0 || strpos( $asset, '//' ) === 0 || strpos( $asset, 'http' ) === 0 ) {
                continue; // Skip external or data URIs for simplicity in this basic version
            }
            if ( in_array( $asset, $downloaded ) ) continue;

            $asset_url = $base_path . ltrim( $asset, '/' );
            $asset_response = wp_remote_get( $asset_url, array( 'timeout' => 10 ) );
            if ( ! is_wp_error( $asset_response ) && wp_remote_retrieve_response_code( $asset_response ) === 200 ) {
                $content = wp_remote_retrieve_body( $asset_response );
                // create directories inside zip if needed
                $zip->addFromString( ltrim( $asset, '/' ), $content );
                $downloaded[] = $asset;
            }
        }

        $zip->close();

        wp_send_json_success( array( 'zip_url' => $zip_url ) );
    }
}
