<?php
/**
 * Module: Bulk CSV Importer
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Webgames_CSV_Importer {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_csv_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'wp_ajax_webgames_csv_import_row', array( $this, 'handle_import_row_ajax' ) );
        add_action( 'wp_ajax_webgames_csv_download_template', array( $this, 'download_template' ) );
    }

    public function add_csv_menu() {
        add_submenu_page(
            'edit.php?post_type=game',
            __( 'CSV Importer', 'webgames-scrapper' ),
            __( 'CSV Importer', 'webgames-scrapper' ),
            'edit_posts',
            'wg-csv-importer',
            array( $this, 'render_admin_page' )
        );
    }

    public function enqueue_assets( $hook ) {
        if ( $hook === 'game_page_wg-csv-importer' ) {
            wp_enqueue_style( 'wg-csv-importer-css', WEBGAMES_SCRAPPER_URL . 'assets/css/csv-importer.css', array(), WEBGAMES_SCRAPPER_VERSION );
            // Use PapaParse from CDN for CSV parsing
            wp_enqueue_script( 'papaparse', 'https://cdnjs.cloudflare.com/ajax/libs/PapaParse/5.4.1/papaparse.min.js', array(), '5.4.1', true );
            wp_enqueue_script( 'wg-csv-importer-js', WEBGAMES_SCRAPPER_URL . 'assets/js/csv-importer.js', array( 'jquery', 'papaparse' ), WEBGAMES_SCRAPPER_VERSION, true );
            
            wp_localize_script( 'wg-csv-importer-js', 'wgCsvAjax', array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'wg_csv_nonce' ),
                'sources'  => Webgames_Source_Registry::get_sources(),
                'msg_parsing' => __( 'Parsing CSV...', 'webgames-scrapper' ),
                'msg_importing' => __( 'Importing...', 'webgames-scrapper' ),
                'msg_complete' => __( 'Import Complete!', 'webgames-scrapper' ),
            ) );
        }
    }

    public function render_admin_page() {
        ?>
        <div class="wrap wg-csv-wrap">
            <h1><?php esc_html_e( 'CSV Importer', 'webgames-scrapper' ); ?></h1>
            <p><?php esc_html_e( 'Bulk import games from a CSV file. The process runs locally in your browser to prevent server timeouts.', 'webgames-scrapper' ); ?></p>
            
            <div class="wg-csv-card">
                <div class="wg-csv-form-group">
                    <label for="wg-csv-source"><?php esc_html_e( 'Global Game Source:', 'webgames-scrapper' ); ?></label>
                    <select id="wg-csv-source" class="widefat">
                        <?php
                        $sources = Webgames_Source_Registry::get_sources();
                        foreach ( $sources as $key => $data ) {
                            echo '<option value="' . esc_attr( $key ) . '">' . esc_html( $data['label'] ) . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="wg-csv-form-group">
                    <label><?php esc_html_e( 'Duplicate Handling Strategy:', 'webgames-scrapper' ); ?></label>
                    <div class="wg-radio-group">
                        <label>
                            <input type="radio" name="duplicate_strategy" value="skip" checked> 
                            <?php esc_html_e( 'Skip (Do not import if URL exists)', 'webgames-scrapper' ); ?>
                        </label>
                        <br>
                        <label>
                            <input type="radio" name="duplicate_strategy" value="replace"> 
                            <?php esc_html_e( 'Update (Update existing post data if URL exists)', 'webgames-scrapper' ); ?>
                        </label>
                    </div>
                </div>

                <div class="wg-csv-form-group">
                    <label for="wg-csv-file"><?php esc_html_e( 'Select CSV File:', 'webgames-scrapper' ); ?></label>
                    <input type="file" id="wg-csv-file" accept=".csv" />
                    <p class="description">
                        <?php esc_html_e( 'Required columns: STT, URL, HTML, Categories, Tags. ', 'webgames-scrapper' ); ?>
                        <a href="<?php echo esc_url( admin_url( 'admin-ajax.php?action=webgames_csv_download_template' ) ); ?>" class="wg-download-link" target="_blank"><?php esc_html_e( 'Download Sample CSV', 'webgames-scrapper' ); ?></a>
                    </p>
                </div>

                <button type="button" id="wg-btn-start-import" class="button button-primary button-hero">
                    <?php esc_html_e( 'Start Import', 'webgames-scrapper' ); ?>
                </button>
            </div>

            <div class="wg-csv-progress-container" style="display:none;">
                <h3><?php esc_html_e( 'Import Progress', 'webgames-scrapper' ); ?> <span id="wg-csv-counter">(0/0)</span></h3>
                <div class="wg-progress-bar-wrapper">
                    <div class="wg-progress-bar-fill" id="wg-csv-progress-fill"></div>
                </div>
                
                <table class="wp-list-table widefat fixed striped" style="margin-top:20px;">
                    <thead>
                        <tr>
                            <th style="width: 50px;">STT</th>
                            <th>URL</th>
                            <th>Status</th>
                            <th>Message</th>
                        </tr>
                    </thead>
                    <tbody id="wg-csv-log-body">
                        <!-- Logs will appear here -->
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    public function download_template() {
        if ( ! current_user_can( 'edit_posts' ) ) wp_die();
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="sample-import.csv"');
        echo "STT,URL,HTML,Categories,Tags\n";
        echo "1,https://gamepix.com/play/example-game,\"<html>...</html>\",\"Action, Arcade\",\"2D\"\n";
        echo "2,https://musicgames.io/game/piano,,Music,Piano\n";
        wp_die();
    }

    public function handle_import_row_ajax() {
        check_ajax_referer( 'wg_csv_nonce', 'security' );

        @ini_set( 'memory_limit', '1024M' );
        @ini_set( 'pcre.backtrack_limit', '100000000' );
        @set_time_limit( 300 );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( __( 'Permission denied.', 'webgames-scrapper' ) );
        }

        $url = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';
        $source = isset( $_POST['source'] ) ? sanitize_text_field( $_POST['source'] ) : '';
        $strategy = isset( $_POST['duplicate_strategy'] ) ? sanitize_text_field( $_POST['duplicate_strategy'] ) : 'skip';
        $raw_html = isset( $_POST['raw_html'] ) ? wp_unslash( $_POST['raw_html'] ) : '';
        
        $categories_str = isset( $_POST['categories'] ) ? sanitize_text_field( wp_unslash( $_POST['categories'] ) ) : '';
        $tags_str = isset( $_POST['tags'] ) ? sanitize_text_field( wp_unslash( $_POST['tags'] ) ) : '';
        $custom_title = isset( $_POST['title_override'] ) ? sanitize_text_field( wp_unslash( $_POST['title_override'] ) ) : '';

        if ( empty( $url ) ) {
            wp_send_json_error( __( 'Empty URL.', 'webgames-scrapper' ) );
        }

        $sources = Webgames_Source_Registry::get_sources();
        if ( ! isset( $sources[ $source ] ) ) {
            wp_send_json_error( __( 'Invalid Game Source.', 'webgames-scrapper' ) );
        }
        $source_info = $sources[ $source ];

        if ( $source === 'gamepix' ) {
            add_filter( 'http_request_args', function( $args, $url ) {
                if ( preg_match('/\.(jpg|jpeg|png|gif|webp|svg|bmp)(\?.*)?$/i', $url) || strpos( $url, 'gamepix.com' ) !== false ) {
                    if ( ! isset( $args['headers'] ) ) $args['headers'] = array();
                    $args['headers']['User-Agent'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36';
                    $args['headers']['Accept'] = 'image/webp,image/apng,image/svg+xml,image/*,*/*;q=0.8';
                    $args['headers']['Referer'] = 'https://www.gamepix.com/';
                }
                return $args;
            }, 10, 2 );
        }

        $existing_post_id = $this->get_duplicate_post_id( $url, '' );
        if ( $existing_post_id ) {
            if ( $strategy === 'skip' ) {
                wp_send_json_success( array( 'status' => 'skipped', 'msg' => 'Skipped: Duplicate URL' ) );
            }
        }

        $html = $raw_html;
        if ( empty( $html ) ) {
            $args = array(
                'timeout' => 30,
                'headers' => array(
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36',
                    'Accept'     => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                    'Accept-Language' => 'en-US,en;q=0.5',
                )
            );
            $response = wp_remote_get( $url, $args );
            if ( is_wp_error( $response ) ) wp_send_json_error( $response->get_error_message() );
            $status_code = wp_remote_retrieve_response_code( $response );
            if ( $status_code == 403 || $status_code == 503 ) wp_send_json_error( 'Blocked by Cloudflare/Server.' );
            $html = wp_remote_retrieve_body( $response );
            if ( empty( $html ) ) wp_send_json_error( 'Empty response.' );
        }

        libxml_use_internal_errors( true );
        $dom = new DOMDocument();
        $dom->loadHTML( mb_convert_encoding( $html, 'HTML-ENTITIES', 'UTF-8' ) );
        $xpath = new DOMXPath( $dom );

        $parser_class = $source_info['parser_class'];
        if ( ! class_exists( $parser_class ) ) wp_send_json_error( 'Parser class not found.' );
        $parser = new $parser_class();
        $parser->set_dom( $dom, $xpath, $html );

        $description = $parser->get_description();

        if ( ! empty( $description ) ) {
            $upload_dir = wp_upload_dir();
            $baseurl = $upload_dir['baseurl'];
            $desc_dom = new DOMDocument();
            $desc_dom->loadHTML( mb_convert_encoding( '<div>' . $description . '</div>', 'HTML-ENTITIES', 'UTF-8' ), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
            $images = $desc_dom->getElementsByTagName('img');
            $has_changes = false;

            foreach ( $images as $img ) {
                $src = $img->getAttribute('src');
                if ( ! empty( $src ) && filter_var( $src, FILTER_VALIDATE_URL ) && strpos( $src, $baseurl ) === false ) {
                    $image_id = $this->sideload_image_no_duplicate( $src, null );
                    if ( ! is_wp_error( $image_id ) ) {
                        $local_url = wp_get_attachment_url( $image_id );
                        if ( $local_url ) {
                            $img->setAttribute('src', $local_url);
                            $img->removeAttribute('srcset');
                            $img->removeAttribute('sizes');
                            $has_changes = true;
                        }
                    }
                }
            }
            if ( $has_changes ) {
                $new_description = '';
                $wrapper = $desc_dom->getElementsByTagName('div')->item(0);
                if ( $wrapper ) {
                    foreach ( $wrapper->childNodes as $child ) {
                        $new_description .= $desc_dom->saveHTML( $child );
                    }
                    $description = $new_description;
                }
            }
        }

        $parsed_title = $parser->get_title();
        $final_title = ! empty( $custom_title ) ? $custom_title : $parsed_title;

        $data = array(
            'title'               => $final_title,
            'description'         => $description,
            'image_url'           => $parser->get_image_url(),
            'image_id'            => '',
            'iframe_url'          => $parser->get_iframe_url(),
            'original_iframe_url' => $parser->get_iframe_url(),
            'custom_meta'         => method_exists( $parser, 'get_custom_meta' ) ? $parser->get_custom_meta() : null,
        );

        if ( empty( $data['title'] ) && empty( $data['iframe_url'] ) ) {
            wp_send_json_error( 'Cannot find game data. Page structure changed or blocked.' );
        }

        if ( ! $existing_post_id ) {
            $existing_post_id = $this->get_duplicate_post_id( '', $data['original_iframe_url'] );
            if ( $existing_post_id && $strategy === 'skip' ) {
                wp_send_json_success( array( 'status' => 'skipped', 'msg' => 'Skipped: Duplicate Iframe' ) );
            }
        }

        if ( $source !== 'gamepix' ) {
            $sideload_result = $this->maybe_sideload_game( $data['original_iframe_url'], $data['title'], $source );
            if ( $sideload_result ) {
                $data['iframe_url'] = $sideload_result['local_url'];
            }
        }

        if ( ! empty( $data['image_url'] ) ) {
            $image_id = $this->sideload_image_no_duplicate( $data['image_url'], $data['title'] );
            if ( ! is_wp_error( $image_id ) ) {
                $data['image_id'] = $image_id;
            }
        }

        $post_data = array(
            'post_type'   => 'game',
            'post_title'  => wp_strip_all_tags( $data['title'] ),
            'post_content'=> $data['description'],
        );

        if ( $strategy === 'replace' && ! empty( $existing_post_id ) ) {
            $post_data['ID'] = $existing_post_id;
            $post_id = wp_update_post( $post_data );
            $msg = 'Updated successfully';
        } else {
            $post_data['post_status'] = 'private';
            $post_id = wp_insert_post( $post_data );
            $msg = 'Imported successfully';
        }

        if ( is_wp_error( $post_id ) ) {
            wp_send_json_error( 'Failed to save post.' );
        }

        $this->assign_taxonomies( $post_id, $categories_str, 'game-category' );
        $this->assign_taxonomies( $post_id, $tags_str, 'game-tag' );

        if ( function_exists( 'update_field' ) ) {
            update_field( 'field_webgames_source_type', 'iframe', $post_id );
            update_field( 'field_webgames_iframe_url', $data['iframe_url'], $post_id );
            if ( ! empty( $data['image_id'] ) ) {
                update_field( 'field_webgames_game_cover', $data['image_id'], $post_id );
            }
        } else {
            update_post_meta( $post_id, 'source_type', 'iframe' );
            update_post_meta( $post_id, 'iframe_url', $data['iframe_url'] );
            if ( ! empty( $data['image_id'] ) ) {
                update_post_meta( $post_id, 'game_cover', $data['image_id'] );
            }
        }

        if ( ! empty( $data['image_id'] ) ) {
            set_post_thumbnail( $post_id, $data['image_id'] );
        }

        update_post_meta( $post_id, '_wg_scraped_source_url', $url );
        update_post_meta( $post_id, '_wg_original_iframe_url', $data['original_iframe_url'] );
        
        if ( ! empty( $data['custom_meta'] ) ) {
            update_post_meta( $post_id, '_wg_gamepix_metadata', $data['custom_meta'] );
        } else {
            delete_post_meta( $post_id, '_wg_gamepix_metadata' );
        }

        wp_send_json_success( array( 'status' => 'success', 'msg' => $msg ) );
    }

    private function assign_taxonomies( $post_id, $terms_str, $taxonomy ) {
        if ( empty( $terms_str ) ) return;
        
        $term_names = array_map( 'trim', explode( ',', $terms_str ) );
        $term_ids = array();

        foreach ( $term_names as $name ) {
            if ( empty( $name ) ) continue;
            
            $term = term_exists( $name, $taxonomy );
            if ( ! $term ) {
                $term = wp_insert_term( $name, $taxonomy );
            }
            if ( ! is_wp_error( $term ) && isset( $term['term_id'] ) ) {
                $term_ids[] = (int) $term['term_id'];
            }
        }

        if ( ! empty( $term_ids ) ) {
            wp_set_object_terms( $post_id, $term_ids, $taxonomy, true );
        }
    }

    private function get_duplicate_post_id( $url, $iframe_url ) {
        global $wpdb;
        $urls_to_check = array();
        
        if ( ! empty( $url ) ) {
            $clean = untrailingslashit( $url );
            $urls_to_check[] = $wpdb->prepare( "(meta_key = '_wg_scraped_source_url' AND (meta_value = %s OR meta_value = %s))", $clean, $clean . '/' );
        }
        if ( ! empty( $iframe_url ) ) {
            $clean = untrailingslashit( $iframe_url );
            $urls_to_check[] = $wpdb->prepare( "((meta_key = '_wg_original_iframe_url' OR meta_key = 'iframe_url') AND (meta_value = %s OR meta_value = %s))", $clean, $clean . '/' );
        }
        if ( empty( $urls_to_check ) ) return false;
        
        $where = implode( ' OR ', $urls_to_check );
        return $wpdb->get_var( "SELECT post_id FROM $wpdb->postmeta WHERE $where LIMIT 1" );
    }

    private function sideload_image_no_duplicate( $url, $title = null ) {
        $existing_images = get_posts( array(
            'post_type'  => 'attachment',
            'post_status'=> 'inherit',
            'meta_key'   => '_wg_original_image_url',
            'meta_value' => esc_url_raw( $url ),
            'fields'     => 'ids',
            'numberposts'=> 1,
        ) );
        if ( ! empty( $existing_images ) ) return $existing_images[0];

        require_once( ABSPATH . 'wp-admin/includes/media.php' );
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        require_once( ABSPATH . 'wp-admin/includes/image.php' );
        
        $image_id = media_sideload_image( $url, 0, $title, 'id' );
        if ( ! is_wp_error( $image_id ) ) {
            update_post_meta( $image_id, '_wg_original_image_url', esc_url_raw( $url ) );
        }
        return $image_id;
    }

    private function maybe_sideload_game( $iframe_url, $title, $source ) {
        if ( empty( $iframe_url ) ) return false;
        $response = wp_remote_head( $iframe_url, array( 'timeout' => 5 ) );
        if ( is_wp_error( $response ) ) $response = wp_remote_get( $iframe_url, array( 'timeout' => 5, 'stream' => true ) );

        $blocked = false;
        $code = wp_remote_retrieve_response_code( $response );
        if ( $code == 403 || $code == 401 ) {
            $blocked = true;
        } else {
            $x_frame = wp_remote_retrieve_header( $response, 'x-frame-options' );
            if ( ! empty( $x_frame ) && preg_match( '/(DENY|SAMEORIGIN)/i', $x_frame ) ) $blocked = true;
            $csp = wp_remote_retrieve_header( $response, 'content-security-policy' );
            if ( ! empty( $csp ) && stripos( $csp, 'frame-ancestors' ) !== false ) {
                $host = $_SERVER['HTTP_HOST'];
                if ( stripos( $csp, $host ) === false && stripos( $csp, '*' ) === false ) $blocked = true;
            }
        }
        if ( ! $blocked ) return false;

        $get_response = wp_remote_get( $iframe_url, array( 'timeout' => 60 ) );
        if ( is_wp_error( $get_response ) ) return false;
        $html = wp_remote_retrieve_body( $get_response );
        unset( $get_response );
        if ( empty( $html ) ) return false;

        if ( stripos( $html, 'loading-text' ) !== false ) {
            $html = preg_replace(
                '/(<[^>]+class=["\'][^"\']*loading-text[^"\']*["\'][^>]*>)(.*?)(<\/[^>]+>)/i',
                '$1<script>document.write(window.location.hostname || "miniwebgames.com")</script>$3',
                $html, 1
            );
        }

        $host = sanitize_text_field( $_SERVER['HTTP_HOST'] );
        $framebuster = "<script>\nif (window.top !== window.self) {\n    var isAllowed = false;\n    var allowed = '{$host}';\n    if (window.location.ancestorOrigins) {\n        for (var i = 0; i < window.location.ancestorOrigins.length; i++) {\n            if (window.location.ancestorOrigins[i].indexOf(allowed) !== -1) {\n                isAllowed = true; break;\n            }\n        }\n    } else {\n        try { if (document.referrer.indexOf(allowed) !== -1) isAllowed = true; } catch(e){}\n    }\n    if (!isAllowed) {\n        document.documentElement.innerHTML = '<div style=\"color:white;background:black;height:100vh;display:flex;align-items:center;justify-content:center;font-family:sans-serif;\"><h1>Hotlinking is disabled.</h1></div>';\n        throw new Error('Hotlinking disabled.');\n    }\n}\n</script>\n";

        if ( stripos( $html, '<head>' ) !== false ) {
            $html = preg_replace( '/<head>/i', '<head>' . "\n" . $framebuster, $html, 1 );
        } else {
            $html = $framebuster . $html;
        }

        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        add_filter( 'filesystem_method', function() { return 'direct'; }, 100 );
        WP_Filesystem();
        global $wp_filesystem;
        if ( ! is_object( $wp_filesystem ) ) return false;

        $upload_dir = wp_upload_dir();
        $base_dir = $upload_dir['basedir'] . '/webgames';
        $source_dir = $base_dir . '/' . sanitize_file_name( $source );
        $slug = sanitize_title( $title );
        if ( empty( $slug ) ) $slug = 'game-' . time();
        $slug_dir = $source_dir . '/' . $slug;
        
        if ( ! $wp_filesystem->is_dir( $base_dir ) ) {
            $wp_filesystem->mkdir( $base_dir );
            $htaccess = "<IfModule mod_headers.c>\n    Header set X-Frame-Options \"SAMEORIGIN\"\n    Header set Content-Security-Policy \"frame-ancestors 'self'\"\n</IfModule>\n";
            $wp_filesystem->put_contents( $base_dir . '/.htaccess', $htaccess );
        }
        if ( ! $wp_filesystem->is_dir( $source_dir ) ) $wp_filesystem->mkdir( $source_dir );
        if ( ! $wp_filesystem->is_dir( $slug_dir ) ) $wp_filesystem->mkdir( $slug_dir );

        $file_path = $slug_dir . '/index.html';
        $written = $wp_filesystem->put_contents( $file_path, $html );
        
        if ( $written ) {
            return array(
                'local_url' => $upload_dir['baseurl'] . '/webgames/' . sanitize_file_name( $source ) . '/' . $slug . '/index.html'
            );
        }
        return false;
    }
}
