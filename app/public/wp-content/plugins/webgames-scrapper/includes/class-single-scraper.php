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
                <?php
                $sources = Webgames_Source_Registry::get_sources();
                foreach ( $sources as $key => $data ) {
                    echo '<option value="' . esc_attr( $key ) . '">' . esc_html( $data['label'] ) . '</option>';
                }
                ?>
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
            <?php $existing_original_iframe = get_post_meta( $post->ID, '_wg_original_iframe_url', true ); ?>
            <input type="hidden" id="wg_original_iframe_url" name="wg_original_iframe_url" value="<?php echo esc_attr( $existing_original_iframe ); ?>" />
        </div>
        <?php
    }

    public function save_scraper_meta( $post_id ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;
        
        if ( isset( $_POST['wg_scraped_source_url'] ) && ! empty( $_POST['wg_scraped_source_url'] ) ) {
            update_post_meta( $post_id, '_wg_scraped_source_url', esc_url_raw( $_POST['wg_scraped_source_url'] ) );
        }
        if ( isset( $_POST['wg_original_iframe_url'] ) && ! empty( $_POST['wg_original_iframe_url'] ) ) {
            update_post_meta( $post_id, '_wg_original_iframe_url', esc_url_raw( $_POST['wg_original_iframe_url'] ) );
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
                'domain_err'=> __( 'Lỗi: URL nhập vào không thuộc hệ thống của Game Source đã chọn!', 'webgames-scrapper' ),
                'sources'  => Webgames_Source_Registry::get_sources(),
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

        // Validate domain based on registry
        $sources = Webgames_Source_Registry::get_sources();
        if ( ! isset( $sources[ $source ] ) ) {
            wp_send_json_error( __( 'Invalid Game Source.', 'webgames-scrapper' ) );
        }

        $source_info = $sources[ $source ];
        if ( ! empty( $source_info['domain'] ) ) {
            if ( stripos( $url, $source_info['domain'] ) === false ) {
                wp_send_json_error( __( 'Lỗi: URL nhập vào không thuộc hệ thống của Game Source đã chọn!', 'webgames-scrapper' ) );
            }
        }

        // Fast initial duplicate check before scraping
        $this->check_duplicate_and_exit( $url, '' );

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

        // Strategy Pattern routing via Registry
        $parser_class = $source_info['parser_class'];
        if ( class_exists( $parser_class ) ) {
            $parser = new $parser_class();
        } else {
            wp_send_json_error( __( 'Parser class not found.', 'webgames-scrapper' ) );
        }

        $parser->set_dom( $dom, $xpath, $html );

        $description = $parser->get_description();

        // Sideload images inside description
        if ( ! empty( $description ) ) {
            set_time_limit(0); // Prevent timeout for many images

            require_once( ABSPATH . 'wp-admin/includes/media.php' );
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
            require_once( ABSPATH . 'wp-admin/includes/image.php' );

            $upload_dir = wp_upload_dir();
            $baseurl = $upload_dir['baseurl'];

            libxml_use_internal_errors( true );
            $desc_dom = new DOMDocument();
            // Use mb_convert_encoding and a wrapper div to ensure valid HTML and correct UTF-8 handling
            $desc_dom->loadHTML( mb_convert_encoding( '<div>' . $description . '</div>', 'HTML-ENTITIES', 'UTF-8' ), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
            
            $images = $desc_dom->getElementsByTagName('img');
            $has_changes = false;

            foreach ( $images as $img ) {
                $src = $img->getAttribute('src');
                if ( ! empty( $src ) && filter_var( $src, FILTER_VALIDATE_URL ) ) {
                    // Check if it's already on our server
                    if ( strpos( $src, $baseurl ) === false ) {
                        // Sideload the image without duplicating
                        $image_id = $this->sideload_image_no_duplicate( $src, null );
                        if ( ! is_wp_error( $image_id ) ) {
                            $local_url = wp_get_attachment_url( $image_id );
                            if ( $local_url ) {
                                $img->setAttribute('src', $local_url);
                                $img->removeAttribute('srcset');
                                $img->removeAttribute('sizes');
                                $has_changes = true;
                            }
                        } else {
                            error_log( 'Webgames Scraper Content Image Error: ' . $image_id->get_error_message() . ' URL: ' . $src );
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

        $data = array(
            'title'               => $parser->get_title(),
            'description'         => $description,
            'image_url'           => $parser->get_image_url(),
            'image_id'            => '',
            'image_error'         => '',
            'iframe_url'          => $parser->get_iframe_url(),
            'source_url'          => $url, // Pass back to JS for tracking
            'original_iframe_url' => $parser->get_iframe_url(),
            'download_msg'        => '',
        );

        // Robust duplicate check including iframe URL
        $this->check_duplicate_and_exit( $url, $data['original_iframe_url'] );

        // Smart Sideloading (CSP Checker)
        $sideload_result = $this->maybe_sideload_game( $data['original_iframe_url'], $data['title'], $source );
        if ( $sideload_result ) {
            $data['iframe_url'] = $sideload_result['local_url'];
            $data['download_msg'] = $sideload_result['msg'];
        }

        // Sideload image if available
        if ( ! empty( $data['image_url'] ) ) {
            $image_id = $this->sideload_image_no_duplicate( $data['image_url'], $data['title'] );
            if ( ! is_wp_error( $image_id ) ) {
                $data['image_id'] = $image_id;
            } else {
                $data['image_error'] = $image_id->get_error_message();
                error_log( 'Webgames Scraper Featured Image Error: ' . $data['image_error'] . ' URL: ' . $data['image_url'] );
            }
        }

        wp_send_json_success( $data );
    }

    private function sideload_image_no_duplicate( $url, $title = null ) {
        // Query to check if this URL is already downloaded
        $existing_images = get_posts( array(
            'post_type'  => 'attachment',
            'post_status'=> 'inherit',
            'meta_key'   => '_wg_original_image_url',
            'meta_value' => esc_url_raw( $url ),
            'fields'     => 'ids',
            'numberposts'=> 1,
        ) );

        if ( ! empty( $existing_images ) ) {
            return $existing_images[0];
        }

        require_once( ABSPATH . 'wp-admin/includes/media.php' );
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        require_once( ABSPATH . 'wp-admin/includes/image.php' );
        
        $image_id = media_sideload_image( $url, 0, $title, 'id' );
        
        if ( ! is_wp_error( $image_id ) ) {
            update_post_meta( $image_id, '_wg_original_image_url', esc_url_raw( $url ) );
        }

        return $image_id;
    }

    private function check_duplicate_and_exit( $url, $iframe_url ) {
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
        
        if ( empty( $urls_to_check ) ) return;
        
        $where = implode( ' OR ', $urls_to_check );
        $duplicate_id = $wpdb->get_var( "SELECT post_id FROM $wpdb->postmeta WHERE $where LIMIT 1" );
        
        if ( $duplicate_id ) {
            $edit_url = get_edit_post_link( $duplicate_id, 'raw' );
            $error_msg = sprintf(
                __( 'Duplicate! This game already exists in the system. <a href="%s" target="_blank" style="text-decoration:underline;">Click here to edit it</a>.', 'webgames-scrapper' ),
                esc_url( $edit_url )
            );
            wp_send_json_error( $error_msg );
        }
    }

    private function maybe_sideload_game( $iframe_url, $title, $source ) {
        if ( empty( $iframe_url ) ) return false;

        $response = wp_remote_head( $iframe_url, array( 'timeout' => 5 ) );
        if ( is_wp_error( $response ) ) {
            $response = wp_remote_get( $iframe_url, array( 'timeout' => 5, 'stream' => true ) );
        }

        $blocked = false;
        $code = wp_remote_retrieve_response_code( $response );
        
        if ( $code == 403 || $code == 401 ) {
            $blocked = true;
        } else {
            $x_frame = wp_remote_retrieve_header( $response, 'x-frame-options' );
            if ( ! empty( $x_frame ) && preg_match( '/(DENY|SAMEORIGIN)/i', $x_frame ) ) {
                $blocked = true;
            }
            $csp = wp_remote_retrieve_header( $response, 'content-security-policy' );
            if ( ! empty( $csp ) && stripos( $csp, 'frame-ancestors' ) !== false ) {
                $host = $_SERVER['HTTP_HOST'];
                if ( stripos( $csp, $host ) === false && stripos( $csp, '*' ) === false ) {
                    $blocked = true;
                }
            }
        }

        if ( ! $blocked ) return false;

        $get_response = wp_remote_get( $iframe_url, array( 'timeout' => 60 ) );
        if ( is_wp_error( $get_response ) ) return false;
        
        $html = wp_remote_retrieve_body( $get_response );
        if ( empty( $html ) ) return false;

        // Auto-replace loading text with dynamic domain script at download time (with fallback for file:// protocol)
        $html = preg_replace(
            '/(<[^>]+class=["\'][^"\']*loading-text[^"\']*["\'][^>]*>)(.*?)(<\/[^>]+>)/i',
            '$1<script>document.write(window.location.hostname || "miniwebgames.com")</script>$3',
            $html
        );

        $host = sanitize_text_field( $_SERVER['HTTP_HOST'] );
        $framebuster = "<script>
if (window.top !== window.self) {
    var isAllowed = false;
    var allowed = '{$host}';
    if (window.location.ancestorOrigins) {
        for (var i = 0; i < window.location.ancestorOrigins.length; i++) {
            if (window.location.ancestorOrigins[i].indexOf(allowed) !== -1) {
                isAllowed = true; break;
            }
        }
    } else {
        try { if (document.referrer.indexOf(allowed) !== -1) isAllowed = true; } catch(e){}
    }
    if (!isAllowed) {
        document.documentElement.innerHTML = '<div style=\"color:white;background:black;height:100vh;display:flex;align-items:center;justify-content:center;font-family:sans-serif;\"><h1>Hotlinking is disabled.</h1></div>';
        throw new Error('Hotlinking disabled.');
    }
}
</script>\n";

        if ( stripos( $html, '<head>' ) !== false ) {
            $html = preg_replace( '/<head>/i', '<head>' . "\n" . $framebuster, $html, 1 );
        } else {
            $html = $framebuster . $html;
        }

        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        WP_Filesystem();
        global $wp_filesystem;

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
        if ( ! $wp_filesystem->is_dir( $source_dir ) ) {
            $wp_filesystem->mkdir( $source_dir );
        }
        if ( ! $wp_filesystem->is_dir( $slug_dir ) ) {
            $wp_filesystem->mkdir( $slug_dir );
        }

        $file_path = $slug_dir . '/index.html';
        $written = $wp_filesystem->put_contents( $file_path, $html );
        
        if ( $written ) {
            $local_url = $upload_dir['baseurl'] . '/webgames/' . sanitize_file_name( $source ) . '/' . $slug . '/index.html';
            return array(
                'local_url' => $local_url,
                'msg'       => __( 'Server gốc chặn nhúng (CSP/X-Frame). Hệ thống đã tải file index.html về local. (Lưu ý: Nếu game chứa nhiều asset ngoài, có thể cần tải thủ công bằng Downloader).', 'webgames-scrapper' )
            );
        }

        return false;
    }
}
