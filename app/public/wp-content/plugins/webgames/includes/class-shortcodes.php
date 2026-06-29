<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Webgames_Shortcodes {

    public function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
        add_shortcode( 'webgames_player', array( $this, 'render_player' ) );
        add_shortcode( 'webgames_sidebar_list', array( $this, 'render_sidebar_list' ) );
        add_shortcode( 'webgames_category_menu', array( $this, 'render_category_menu' ) );
        add_shortcode( 'webgames_related_grid', array( $this, 'render_related_grid' ) );
        add_shortcode( 'webgames_breadcrumbs', array( $this, 'render_breadcrumbs' ) );
        add_shortcode( 'webgames_archive_content', array( $this, 'archive_content_shortcode' ) );
        add_shortcode( 'webgames_favorite_games', array( $this, 'favorite_games_shortcode' ) );
        add_shortcode( 'webgames_rating', array( $this, 'render_rating' ) );
        add_shortcode( 'webgames_header_user', array( $this, 'render_header_user' ) );

        // AJAX for favorites
        add_action( 'wp_ajax_webgames_get_favorites', array( $this, 'ajax_get_favorites' ) );
        add_action( 'wp_ajax_nopriv_webgames_get_favorites', array( $this, 'ajax_get_favorites' ) );

        add_shortcode( 'webgames_recently_played', array( $this, 'recently_played_shortcode' ) );
        add_action( 'wp_ajax_webgames_get_recently_played', array( $this, 'ajax_get_recently_played' ) );
        add_action( 'wp_ajax_nopriv_webgames_get_recently_played', array( $this, 'ajax_get_recently_played' ) );

        add_shortcode( 'webgames_discover_menu', array( $this, 'discover_menu_shortcode' ) );
        add_shortcode( 'webgames_all_tags', array( $this, 'all_tags_shortcode' ) );
        add_shortcode( 'webgames_game_slider', array( $this, 'game_slider_shortcode' ) );
        add_shortcode( 'webgames_home_ad', array( $this, 'home_ad_shortcode' ) );

        // Magic tag filter for icon
        add_filter( 'render_block_core/navigation-link', array( $this, 'filter_nav_link_icon' ), 10, 2 );
        add_filter( 'render_block_core/list-item', array( $this, 'filter_nav_link_icon' ), 10, 2 );
        add_filter( 'the_content', array( $this, 'filter_content_icon' ) );

        // Dynamic swap for game search
        add_filter( 'render_block', array( $this, 'swap_search_blocks' ), 10, 2 );

        // Global minification for all webgames shortcodes to prevent wpautop issues
        add_filter( 'do_shortcode_tag', array( $this, 'minify_shortcode_output' ), 99, 2 );
    }

    /**
     * Globally strip HTML comments and newlines from all webgames shortcodes
     * to prevent WordPress wpautop from injecting empty <p> tags.
     */
    public function minify_shortcode_output( $output, $tag = '' ) {
        if ( empty( $tag ) || strpos( $tag, 'webgames_' ) === 0 ) {
            // Xóa HTML comments
            $output = preg_replace('/<!--(.|\s)*?-->/', '', $output);
            // Xóa toàn bộ dấu xuống dòng và ký tự tab
            $output = str_replace( array( "\r", "\n", "\t" ), '', $output );
            // Xóa các khoảng trắng thừa (indentation) từ 2 khoảng trắng trở lên giữa các thẻ HTML
            $output = preg_replace('/>\s{2,}</', '><', $output);
        }
        return $output;
    }

    public function swap_search_blocks( $block_content, $block ) {
        if ( is_admin() ) {
    return $block_content;
}
if ( is_search() && isset( $_GET['post_type'] ) && $_GET['post_type'] === 'game' ) {
    if ( $block['blockName'] === 'core/query' ) {
                static $rendered = false;
                if ( ! $rendered ) {
                    $rendered = true;
                    // Add Top Ad before the grid
                    $ad_html = '<div class="wg-ad-container" style="margin-top:20px;margin-bottom:20px;">'
    . '<div class="wg-top-ad-placeholder" style="background: #2f3542; color: #fff; border-radius: 8px; padding: 20px; width: 100%; max-width: 728px; margin: 0 auto; display: flex; align-items: center; justify-content: center; min-height: 90px;">'
    . 'Top Leaderboard Ad (728x90)'
    . '</div>'
    . '</div>';
                    return $ad_html . do_shortcode( '[webgames_archive_content]' );
                }
                return ''; // If multiple queries exist, hide them
            }
            if ( $block['blockName'] === 'core/query-title' ) {
                return ''; // Hide default title
            }
        }
        return $block_content;
    }

    public function register_assets() {
        wp_enqueue_style( 'dashicons' );
        wp_enqueue_style( 'webgames-header-style', WEBGAMES_PLUGIN_URL . 'assets/css/header.css', array(), '1.0.0' );
        wp_enqueue_style( 'webgames-elements-style', WEBGAMES_PLUGIN_URL . 'assets/css/elements.css', array(), '1.0.0' );
        wp_enqueue_style( 'webgames-player-style', WEBGAMES_PLUGIN_URL . 'assets/css/player.css', array('webgames-elements-style', 'dashicons'), '1.0.0' );
        wp_enqueue_script( 'webgames-player-js', WEBGAMES_PLUGIN_URL . 'assets/js/player.js', array( 'jquery' ), '1.0.0', true );
        
        wp_localize_script( 'webgames-player-js', 'webgames_ajax', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'webgames_nonce' ),
            'i18n'     => array(
                'like'           => __( 'Like', 'webgames' ),
                'unlike'         => __( 'Unlike', 'webgames' ),
                'dislike'        => __( 'Dislike', 'webgames' ),
                'remove_dislike' => __( 'Remove Dislike', 'webgames' ),
                'fav_add'        => __( 'Added to favorites', 'webgames' ),
                'fav_remove'     => __( 'Removed from favorites', 'webgames' ),
                'unfav'          => __( 'Unfavorite', 'webgames' ),
            )
        ) );
    }

    public function render_player( $atts ) {
        if ( ! is_singular( 'game' ) ) {
            return '';
        }

        $post_id = get_the_ID();
        $source_type = get_post_meta( $post_id, 'source_type', true );
        $game_url = '';

        if ( $source_type === 'html5' ) {
            $game_url = get_post_meta( $post_id, 'html5_url', true );
        } elseif ( $source_type === 'iframe' ) {
            $game_url = get_post_meta( $post_id, 'iframe_url', true );
        }

        if ( empty( $game_url ) ) {
            return '<p>' . __( 'Game source not found.', 'webgames' ) . '</p>';
        }

        $cover_raw = get_post_meta( $post_id, 'game_cover', true );
        $cover_url = '';
        $thumb_url = '';

        if ( is_numeric( $cover_raw ) ) {
            $cover_url = wp_get_attachment_image_url( $cover_raw, 'large' );
            $thumb_url = wp_get_attachment_image_url( $cover_raw, 'medium' );
        } elseif ( ! empty( $cover_raw ) ) {
            $cover_url = $cover_raw;
            $thumb_url = $cover_raw;
        }

        if ( empty( $cover_url ) && has_post_thumbnail( $post_id ) ) {
            $cover_url = get_the_post_thumbnail_url( $post_id, 'large' );
            $thumb_url = get_the_post_thumbnail_url( $post_id, 'medium' );
        }

        $title = get_the_title();

        // Get Label
        $label = get_post_meta( $post_id, 'game_label', true );
        $label_html = '';
        if ( $label === 'hot' ) {
            $label_html = '<span class="wg-label wg-label-hot">HOT</span>';
        } elseif ( $label === 'new' ) {
            $label_html = '<span class="wg-label wg-label-new">NEW</span>';
        }

        // Calculate Totals for Like/Dislike
        $real_like = (int) get_post_meta( $post_id, '_real_like', true );
        $real_dislike = (int) get_post_meta( $post_id, '_real_dislike', true );
        
        $fake_like = get_post_meta( $post_id, 'fake_like', true );
        $fake_like = ( $fake_like !== '' ) ? (int) $fake_like : 100;
        
        $fake_dislike = get_post_meta( $post_id, 'fake_dislike', true );
        $fake_dislike = ( $fake_dislike !== '' ) ? (int) $fake_dislike : 10;
        
        $total_like = $fake_like + $real_like;
        $total_dislike = $fake_dislike + $real_dislike;

        ob_start();
        ?>
        <div class="webgames-player-wrapper" id="webgames-player-wrapper">
            <!-- Game Canvas / Iframe Area -->
            <div class="webgames-canvas-container" id="webgames-canvas-container">
                <div class="wg-btn-exit-wrapper">
                    <button class="wg-btn-exit-fullscreen" id="wg-btn-exit-fullscreen" style="display: none;" title="<?php _e('Exit Fullscreen', 'webgames'); ?>">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M5 16h3v3h2v-5H5v2zm3-8H5v2h5V5H8v3zm6 11h2v-3h3v-2h-5v5zm2-11V5h-2v5h5V8h-3z"/></svg>
                    </button>
                </div>
                <div class="webgames-cover" id="webgames-cover">
                    <div class="wg-cover-bg" style="background-image: url('<?php echo esc_url( $cover_url ); ?>');"></div>
                    <div class="wg-cover-content">
                        <h2 class="wg-cover-title"><?php echo esc_html( $title ); ?></h2>
                        <?php if ( ! empty( $thumb_url ) ) : ?>
                            <div class="wg-cover-thumb-wrapper">
                                <img src="<?php echo esc_url( $thumb_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" class="wg-cover-thumb" />
                            </div>
                        <?php endif; ?>
                        <div class="wg-cover-play-wrap">
                            <button class="webgames-play-btn" id="webgames-play-btn" data-src="<?php echo esc_url( $game_url ); ?>">
                                <span class="dashicons dashicons-controls-play"></span> <?php _e( 'PLAY NOW', 'webgames' ); ?>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="wg-iframe-wrapper">
                    <iframe id="webgames-iframe" class="webgames-iframe" src="" frameborder="0" scrolling="no" allowfullscreen></iframe>
                </div>
            </div>

                <!-- Toolbar Area -->
                <div class="webgames-toolbar">
                    <div class="wg-toolbar-left">
                        <div class="wg-thumb-container" style="position:relative;">
                            <?php if ( ! empty( $thumb_url ) ) : ?>
                                <img src="<?php echo esc_url( $thumb_url ); ?>" alt="thumbnail" class="wg-thumb" />
                            <?php endif; ?>
                            <?php echo $label_html; ?>
                        </div>
                        <div class="wg-title"><?php echo esc_html( $title ); ?></div>
                    </div>
                    <div class="wg-toolbar-right">
                        <button class="wg-btn wg-btn-like" data-post-id="<?php echo esc_attr( $post_id ); ?>" data-tooltip="<?php _e('Like', 'webgames'); ?>">
                            <span class="dashicons dashicons-thumbs-up"></span> <span class="wg-count-like"><?php echo esc_html( webgames_format_number( $total_like ) ); ?></span>
                        </button>
                        <button class="wg-btn wg-btn-dislike" data-post-id="<?php echo esc_attr( $post_id ); ?>" data-tooltip="<?php _e('Dislike', 'webgames'); ?>">
                            <span class="dashicons dashicons-thumbs-down"></span> <span class="wg-count-dislike"><?php echo esc_html( webgames_format_number( $total_dislike ) ); ?></span>
                        </button>
                        <button class="wg-btn wg-btn-fav" data-post-id="<?php echo esc_attr( $post_id ); ?>" data-game-title="<?php echo esc_attr( $title ); ?>" data-game-thumb="<?php echo esc_url( $thumb_url ); ?>" data-tooltip="<?php _e('Favorite', 'webgames'); ?>">
                            <span class="dashicons dashicons-heart"></span>
                        </button>
                        <button class="wg-btn wg-btn-share" id="wg-btn-share" data-game-title="<?php echo esc_attr( $title ); ?>" data-game-url="<?php echo esc_url( get_permalink( $post_id ) ); ?>" data-tooltip="<?php _e('Share', 'webgames'); ?>">
                            <span class="dashicons dashicons-share"></span>
                        </button>
                        <button class="wg-btn wg-btn-report" id="wg-btn-report" data-tooltip="<?php _e('Report', 'webgames'); ?>">
                            <span class="dashicons dashicons-flag"></span>
                        </button>
                        <button class="wg-btn wg-btn-fullscreen" id="wg-btn-fullscreen" data-tooltip="<?php _e('Fullscreen', 'webgames'); ?>">
                            <span class="dashicons dashicons-editor-expand"></span>
                        </button>
                    </div>
            </div>

            <!-- Share Off-Canvas Sidebar -->
            <div id="wg-share-sidebar" class="wg-share-sidebar">
                <div class="wg-share-header">
                    <h3><?php _e('Share this game', 'webgames'); ?></h3>
                    <button id="wg-share-close" class="wg-btn wg-btn-close">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>
                <div class="wg-share-body">
                    <div class="wg-share-grid">
                        <button class="wg-share-btn wg-share-fb" id="wg-share-fb">
                            <span class="dashicons dashicons-facebook-alt"></span>
                            <span>Facebook</span>
                        </button>
                        <button class="wg-share-btn wg-share-x" id="wg-share-x">
                            <span class="dashicons dashicons-twitter"></span>
                            <span>X (Twitter)</span>
                        </button>
                        <button class="wg-share-btn wg-share-instagram" id="wg-share-instagram">
                            <span class="dashicons dashicons-instagram"></span>
                            <span>Instagram</span>
                        </button>
                        <button class="wg-share-btn wg-share-tiktok" id="wg-share-tiktok">
                            <svg width="20" height="20" viewBox="0 0 448 512" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M448,209.91a210.06,210.06,0,0,1-122.77-39.25V349.38A162.55,162.55,0,1,1,185,188.31V278.2a74.62,74.62,0,1,0,52.23,71.18V0l88,0a121.18,121.18,0,0,0,1.86,22.17h0A122.18,122.18,0,0,0,381,102.39a121.43,121.43,0,0,0,67,20.14Z"/></svg>
                            <span>TikTok</span>
                        </button>
                        <button class="wg-share-btn wg-share-copy" id="wg-share-copy">
                            <span class="dashicons dashicons-admin-links"></span>
                            <span>Copy Link</span>
                        </button>
                        <!-- Native Share Button (shown only on mobile/supported browsers) -->
                        <button class="wg-share-btn wg-share-native" id="wg-share-native">
                            <span class="dashicons dashicons-share-alt2"></span>
                            <span>More Options</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Report Off-Canvas Sidebar -->
            <div id="wg-report-sidebar" class="wg-share-sidebar">
                <div class="wg-share-header">
                    <h3><?php _e('Report Game Issue', 'webgames'); ?></h3>
                    <div class="wg-close-wrapper">
                        <button id="wg-report-close" class="wg-btn wg-btn-close">
                            <span class="dashicons dashicons-no-alt"></span>
                        </button>
                    </div>
                </div>
                <div class="wg-share-body wg-report-body">
                    <form id="wg-report-form" class="wg-report-form">
                        <input type="hidden" name="post_id" value="<?php echo esc_attr( $post_id ); ?>">
                        <!-- Honeypot -->
                        <input type="text" name="hp_field" style="display:none !important;" tabindex="-1" autocomplete="off">
                        
                        <div class="wg-form-group">
                            <label for="wg-report-reason"><?php _e('What is the problem?', 'webgames'); ?></label>
                            <textarea id="wg-report-reason" name="reason" rows="5" minlength="10" maxlength="500" placeholder="<?php _e( 'Describe the issue (10-500 chars)...', 'webgames' ); ?>" required></textarea>
                            <div class="wg-char-count"><span id="wg-report-chars">0</span>/500</div>
                        </div>
                        
                        <button type="submit" class="wg-btn-submit" id="wg-btn-submit-report">
                            <span class="dashicons dashicons-flag"></span>
                            <?php _e( 'Submit Report', 'webgames' ); ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php
        $player_html = ob_get_clean();
        
        // Guarantee minification by calling it directly, bypassing any do_shortcode_tag FSE bugs
        $player_html = $this->minify_shortcode_output( $player_html, 'webgames_player' );
        
        return $player_html;
    }

    public function render_sidebar_list( $atts ) {
        $max_items = intval( get_option( 'webgames_sidebar_max_items', 20 ) );
        
        $atts = shortcode_atts( array(
            'posts_per_page' => $max_items,
            'ad_after' => 3, // Show ad after the 3rd game
        ), $atts, 'webgames_sidebar_list' );

        $limit = intval( $atts['posts_per_page'] );
        if ( $limit > $max_items || $limit <= 0 ) {
            $limit = $max_items;
        }

        $query = new WP_Query( array(
            'post_type' => 'game',
            'posts_per_page' => $limit,
            'post_status' => 'publish',
            'ignore_sticky_posts' => 1,
        ) );

        if ( ! $query->have_posts() ) {
            return '';
        }

        ob_start();
        echo '<div class="webgames-sidebar-list">';
        
        $count = 0;
        $ad_after = intval( $atts['ad_after'] );
        $ad_code = get_option( 'webgames_sidebar_ad_code' );

        while ( $query->have_posts() ) {
            $query->the_post();
            $count++;
            
            echo $this->get_game_card_html( get_the_ID(), 'medium', 'wg-sidebar-item wg-game-card' );

            // Output Ad if applicable
            if ( $count === $ad_after && ! empty( $ad_code ) ) {
                echo '<div class="wg-sidebar-ad-wrapper">';
                echo do_shortcode( $ad_code );
                echo '</div>';
            }
        }
        
        echo '</div>';
        wp_reset_postdata();

        return ob_get_clean();
    }

    public function render_category_menu() {
        $terms = get_terms( array(
            'taxonomy' => 'game-category',
            'hide_empty' => false,
        ) );

        if ( is_wp_error( $terms ) || empty( $terms ) ) {
            return '';
        }

        ob_start();
        echo '<ul class="wg-cat-menu">';
        foreach ( $terms as $term ) {
            $link = get_term_link( $term );
            
            $image_id = get_term_meta( $term->term_id, 'wg_category_image', true );
            $icon_class = get_term_meta( $term->term_id, 'wg_category_icon_class', true );
            
            $icon_html = '';
            if ( $image_id ) {
                $image_url = wp_get_attachment_image_url( $image_id, 'thumbnail' );
                if ( $image_url ) {
                    // Inline styles to ensure it aligns nicely with text
                    $icon_html = '<img src="' . esc_url( $image_url ) . '" alt="' . esc_attr( $term->name ) . '" class="wg-cat-icon-img" style="width:18px; height:18px; vertical-align:text-bottom; margin-right:5px; border-radius:3px;" />';
                }
            }
            
            if ( ! $icon_html ) {
                if ( $icon_class ) {
                    $icon_html = '<span class="dashicons ' . esc_attr( $icon_class ) . '"></span> ';
                } else {
                    $icon_html = '<span class="dashicons dashicons-category"></span> ';
                }
            }
            
            echo '<li><a href="' . esc_url( $link ) . '"><span class="wg-cat-icon-wrapper">' . trim($icon_html) . '</span><span class="wg-cat-text">' . esc_html( $term->name ) . '</span></a></li>';
        }
        echo '</ul>';
        return ob_get_clean();
    }

    public function render_related_grid( $atts ) {
        $atts = shortcode_atts( array(
            'posts_per_page' => 8,
        ), $atts, 'webgames_related_grid' );

        $post_id = get_the_ID();
        $query = new WP_Query( array(
            'post_type' => 'game',
            'posts_per_page' => intval( $atts['posts_per_page'] ),
            'post_status' => 'publish',
            'post__not_in' => array( $post_id ),
            'ignore_sticky_posts' => 1,
            'orderby' => 'rand' // Show random related games so it's not always the newest
        ) );

        if ( ! $query->have_posts() ) {
            return '';
        }

        ob_start();
        echo '<div class="wg-related-grid-wrapper">';
        echo '<h3 class="screen-reader-text">' . esc_html__( 'You may also like this', 'webgames' ) . '</h3>';
        echo '<div class="wg-related-grid">';
        
        while ( $query->have_posts() ) {
            $query->the_post();
            echo $this->get_game_card_html( get_the_ID(), 'medium', 'wg-related-item wg-game-card' );
        }
        
        echo '</div>'; // close grid
        echo '</div>'; // close wrapper
        wp_reset_postdata();

        return ob_get_clean();
    }

    public function render_breadcrumbs() {
        $separator = ' <span class="wg-breadcrumb-sep">/</span> ';
        $home = '<a href="' . home_url() . '" class="wg-breadcrumb-home"><span class="dashicons dashicons-admin-home"></span> ' . esc_html__( 'Home', 'webgames' ) . '</a>';
        
        $html = '<div class="wg-breadcrumbs">';
        $html .= $home;

        if ( is_search() ) {
            $html .= $separator . '<span class="wg-breadcrumb-current">' . sprintf( esc_html__( 'Search Results for "%s"', 'webgames' ), get_search_query() ) . '</span>';
        } elseif ( is_tax( 'game-category' ) || is_tax( 'game-tag' ) ) {
            $html .= $separator . '<span class="wg-breadcrumb-current">' . esc_html( single_term_title( '', false ) ) . '</span>';
        } elseif ( is_post_type_archive( 'game' ) ) {
            $html .= $separator . '<span class="wg-breadcrumb-current">' . esc_html__( 'All Games', 'webgames' ) . '</span>';
        } elseif ( is_singular( 'game' ) ) {
            $terms = get_the_terms( get_the_ID(), 'game-category' );
            if ( $terms && ! is_wp_error( $terms ) ) {
                $term = $terms[0]; // Get the first category
                $html .= $separator . '<a href="' . esc_url( get_term_link( $term ) ) . '" class="wg-breadcrumb-cat">' . esc_html( $term->name ) . '</a>';
            }
            $html .= $separator . '<span class="wg-breadcrumb-current">' . esc_html( get_the_title() ) . '</span>';
        } else {
            // For other pages like My Favorite Games
            $html .= $separator . '<span class="wg-breadcrumb-current">' . esc_html( get_the_title() ) . '</span>';
        }
        
        $html .= '</div>';
        
        return $html;
    }

    private function get_game_card_html( $post_id, $image_size = 'medium', $item_class = 'wg-game-card', $is_fav_page = false ) {
        $thumb = get_the_post_thumbnail_url( $post_id, $image_size );
        if ( ! $thumb ) {
            $cover_raw = get_post_meta( $post_id, 'game_cover', true );
            if ( is_numeric( $cover_raw ) ) {
                $thumb = wp_get_attachment_image_url( $cover_raw, $image_size );
            } else {
                $thumb = $cover_raw;
            }
        }

        $label = get_post_meta( $post_id, 'game_label', true );
        $label_html = '';
        if ( $label === 'hot' ) {
            $label_html = '<div class="wg-label wg-label-hot">HOT</div>';
        } elseif ( $label === 'new' ) {
            $label_html = '<div class="wg-label wg-label-new">NEW</div>';
        }

        $title = esc_html( get_the_title( $post_id ) );
        $permalink = esc_url( get_permalink( $post_id ) );

        $html = '<div class="' . esc_attr( $item_class ) . '">';
        $html .= '<div class="wg-sidebar-thumb-container">';
        if ( $thumb ) {
            $html .= '<img src="' . esc_url( $thumb ) . '" alt="' . esc_attr( $title ) . '" class="wg-sidebar-thumb" loading="lazy" />';
        }

        // Calculate and format rating
        $rating = get_post_meta( $post_id, '_game_rating', true );
        if ( $rating === '' ) {
            $real_like = (int) get_post_meta( $post_id, '_real_like', true );
            $real_dislike = (int) get_post_meta( $post_id, '_real_dislike', true );
            
            $fake_like = get_post_meta( $post_id, 'fake_like', true );
            $fake_dislike = get_post_meta( $post_id, 'fake_dislike', true );
            $fake_like = ( $fake_like !== '' ) ? (int) $fake_like : 100;
            $fake_dislike = ( $fake_dislike !== '' ) ? (int) $fake_dislike : 10;
            
            $total_like = $real_like + $fake_like;
            $total_dislike = $real_dislike + $fake_dislike;
            $total_votes = $total_like + $total_dislike;
            
            $rating = ( $total_votes > 0 ) ? round( ( $total_like / $total_votes ) * 100 ) : 0;
        } else {
            $rating = (int) $rating;
        }
        $formatted_rating = number_format( $rating / 10, 1 );
        
        $html .= '<div class="wg-game-card-rating"><span class="dashicons dashicons-star-filled"></span> ' . esc_html( $formatted_rating ) . '</div>';

        $html .= '</div>';
        $html .= $label_html;
        
        $info_class = $is_fav_page ? 'wg-game-card-info is-fav-mode' : 'wg-game-card-info';
        $html .= '<div class="' . $info_class . '">';
        $html .= '<h3 class="wg-game-card-title">';
        $html .= '<a href="' . $permalink . '" class="wg-game-card-link" title="' . esc_attr( $title ) . '">' . $title . '</a>';
        $html .= '</h3>';
        if ( $is_fav_page ) {
            // Using data-tooltip and title, ensuring z-index keeps it clickable
            $html .= '<button class="wg-btn-remove-fav" data-post-id="' . esc_attr( $post_id ) . '" title="' . esc_attr__( 'Remove', 'webgames' ) . '" data-tooltip="' . esc_attr__( 'Remove', 'webgames' ) . '"><span class="dashicons dashicons-trash"></span></button>';
        }
        $html .= '</div>';
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Archive Content Shortcode (Title, Sort, Grid, Ads, Pagination)
     */
    public function archive_content_shortcode( $atts ) {
        $queried_object = get_queried_object();
        if ( ! $queried_object ) {
            return '';
        }

        if ( is_search() ) {
            $paged = isset( $_GET['wg_paged'] ) ? max( 1, intval( $_GET['wg_paged'] ) ) : 1;
        } else {
            if ( get_query_var( 'paged' ) ) {
                $paged = get_query_var( 'paged' );
            } elseif ( get_query_var( 'page' ) ) {
                $paged = get_query_var( 'page' );
            } else {
                $paged = 1;
            }
        }
        
        $sort = isset( $_GET['sort'] ) ? sanitize_text_field( $_GET['sort'] ) : 'latest';
        
        $args = array(
            'post_type'      => 'game',
            'post_status'    => 'publish',
            'posts_per_page' => get_option( 'posts_per_page' ),
            'paged'          => $paged,
        );

        $form_action = '';
        if ( isset( $queried_object->taxonomy ) ) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => $queried_object->taxonomy,
                    'field'    => 'term_id',
                    'terms'    => $queried_object->term_id,
                ),
            );
            $title = $queried_object->name;
            $description = $queried_object->description;
            $bottom_seo_content = get_term_meta( $queried_object->term_id, 'bottom_seo_content', true );
            $form_action = get_term_link( $queried_object );
        } elseif ( is_post_type_archive('game') ) {
            $title = __( 'All Games', 'webgames' );
            $description = '';
            $bottom_seo_content = '';
            $form_action = get_post_type_archive_link( 'game' );
        } elseif ( is_search() ) {
            $search_query = get_search_query();
            /* translators: %s is the search query */
            $title = sprintf( __( 'Search Results for "%s"', 'webgames' ), esc_html( $search_query ) );
            $description = '';
            $bottom_seo_content = '';
            $args['s'] = $search_query;
            $args['post_type'] = 'game';
            $form_action = home_url( '/' );
        } else {
            return '';
        }

        // Sorting Logic
        if ( $sort === 'hot' ) {
            $args['meta_key'] = 'game_label';
            $args['meta_value'] = 'hot';
        } elseif ( $sort === 'new' ) {
            $args['meta_key'] = 'game_label';
            $args['meta_value'] = 'new';
        } elseif ( $sort === 'popular' ) {
            $args['meta_key'] = 'wg_views';
            $args['orderby'] = 'meta_value_num';
            $args['order'] = 'DESC';
        } elseif ( $sort === 'rating' ) {
            $args['meta_key'] = '_game_rating';
            $args['orderby'] = 'meta_value_num';
            $args['order'] = 'DESC';
        } else {
            // latest
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
        }

        $query = new WP_Query( $args );

        ob_start();
        ?>
        <div class="wg-archive-container">
            <!-- Breadcrumbs -->
            <div class="wg-archive-breadcrumbs">
                <?php echo do_shortcode('[webgames_breadcrumbs]'); ?>
            </div>

            <!-- Header Block -->
            <div class="wg-archive-header">
                <div class="wg-archive-header-left">
                    <h1 class="wg-archive-title"><?php echo esc_html( $title ); ?></h1>
                    <?php if ( $description ) : ?>
                        <div class="wg-archive-desc"><?php echo wp_kses_post( $description ); ?></div>
                    <?php endif; ?>
                </div>
                <div class="wg-archive-header-right">
                    <form method="GET" action="<?php echo esc_url( $form_action ); ?>" class="wg-sort-form">
                        <?php
                        // Preserve existing GET params
                        foreach ( $_GET as $key => $val ) {
                            if ( $key !== 'sort' && $key !== 'paged' && $key !== 'page' && $key !== 'wg_paged' ) {
                                if ( is_array( $val ) ) {
                                    foreach ( $val as $v ) {
                                        echo '<input type="hidden" name="' . esc_attr( $key ) . '[]" value="' . esc_attr( $v ) . '" />';
                                    }
                                } else {
                                    echo '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( $val ) . '" />';
                                }
                            }
                        }
                        ?>
                        <label for="wg-sort-select"><?php _e( 'Sort By:', 'webgames' ); ?></label>
                        <select name="sort" id="wg-sort-select" onchange="this.form.submit()">
                            <option value="latest" <?php selected( $sort, 'latest' ); ?>><?php _e( 'Latest', 'webgames' ); ?></option>
                            <option value="new" <?php selected( $sort, 'new' ); ?>><?php _e( 'New', 'webgames' ); ?></option>
                            <option value="hot" <?php selected( $sort, 'hot' ); ?>><?php _e( 'Hot', 'webgames' ); ?></option>
                            <option value="popular" <?php selected( $sort, 'popular' ); ?>><?php _e( 'Most Played', 'webgames' ); ?></option>
                            <option value="rating" <?php selected( $sort, 'rating' ); ?>><?php _e( 'Best Rating', 'webgames' ); ?></option>
                        </select>
                    </form>
                </div>
            </div>

            <!-- Grid Block -->
            <div class="wg-archive-grid-wrapper">
                <?php
                if ( $query->have_posts() ) {
                    $count = 0;
                    while ( $query->have_posts() ) {
                        $query->the_post();
                        
                        // Inject Ad after 4th item (index 3, so when count hits 4)
                        if ( $count === 4 ) {
                            echo '<div class="wg-archive-ad-box">';
                            // Placeholder for 2x2 Ad
                            echo '<div class="wg-ad-placeholder">Ad 4x4 (2x2 Grid)</div>';
                            echo '</div>';
                        }
                        
                        echo $this->get_game_card_html( get_the_ID(), 'medium', 'wg-game-card' );
                        $count++;
                    }
                } else {
                    echo '<p>' . __( 'No games found.', 'webgames' ) . '</p>';
                }
                wp_reset_postdata();
                ?>
            </div>

            <!-- Pagination -->
            <?php if ( $query->max_num_pages > 1 ) : ?>
                <div class="wg-pagination">
                    <?php
                    $big = 999999999; // need an unlikely integer
                    if ( is_search() ) {
                        // Use wg_paged to prevent WP Main Query from 404ing on page 2+
                        // Using $big to avoid URL encoding of %#% by add_query_arg
                        $paginate_base = str_replace( $big, '%#%', esc_url_raw( add_query_arg( 'wg_paged', $big ) ) );
                        $format = '';
                    } else {
                        $paginate_base = str_replace( $big, '%#%', esc_url_raw( get_pagenum_link( $big, false ) ) );
                        $format = '?paged=%#%';
                    }
                    echo paginate_links( array(
                        'base'      => $paginate_base,
                        'format'    => $format,
                        'current'   => max( 1, $paged ),
                        'total'     => $query->max_num_pages,
                        'prev_text' => __( '&laquo; Prev', 'webgames' ),
                        'next_text' => __( 'Next &raquo;', 'webgames' ),
                    ) );
                    ?>
                </div>
            <?php endif; ?>

            <!-- Bottom SEO Block -->
            <?php if ( ! empty( $bottom_seo_content ) ) : ?>
                <div class="wg-content-section wg-bottom-seo">
                    <?php echo wpautop( wp_kses_post( $bottom_seo_content ) ); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Favorite Games Shortcode (AJAX Container)
     */
    public function favorite_games_shortcode( $atts ) {
        ob_start();
        ?>
        <div id="wg-favorite-games-container" style="min-height: 500px;" class="wg-fade-content">
            <div class="wg-archive-container wg-skeleton-container">
                <div class="wg-archive-header" style="height: 40px; margin-bottom: 20px;">
                    <div class="wg-skeleton-pulse" style="width: 250px; height: 32px; border-radius: 5px;"></div>
                </div>
                <div class="wg-archive-grid-wrapper">
                    <?php for ( $i = 0; $i < 12; $i++ ) : ?>
                    <div class="wg-game-card" style="background: transparent; box-shadow: none;">
                        <div class="wg-sidebar-thumb-container wg-skeleton-pulse" style="padding-bottom: 75%; border-radius: 12px; height: 0;"></div>
                        <div class="wg-game-card-info" style="padding: 10px 0;">
                            <div class="wg-skeleton-pulse" style="width: <?php echo rand(60, 90); ?>%; height: 20px; border-radius: 4px;"></div>
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
        <style>
            .wg-fade-content { transition: opacity 0.3s ease; }
            .wg-fade-out { opacity: 0.3; pointer-events: none; }
            .wg-skeleton-pulse {
                background-color: #f0f0f0;
                animation: wg-skeleton-loading 1.5s infinite ease-in-out;
            }
            body.dark-mode .wg-skeleton-pulse, .dark-mode .wg-skeleton-pulse {
                background-color: rgba(255,255,255,0.05);
                animation: wg-skeleton-loading-dark 1.5s infinite ease-in-out;
            }
            @keyframes wg-skeleton-loading {
                0% { background-color: #f0f0f0; }
                50% { background-color: #e0e0e0; }
                100% { background-color: #f0f0f0; }
            }
            @keyframes wg-skeleton-loading-dark {
                0% { background-color: rgba(255,255,255,0.05); }
                50% { background-color: rgba(255,255,255,0.1); }
                100% { background-color: rgba(255,255,255,0.05); }
            }
            .wg-favorite-empty { text-align: center; color: #a4b0be; padding: 60px 0; font-size: 16px; }
            .wg-favorite-empty .dashicons { font-size: 60px; width: 60px; height: 60px; color: #ff4757; margin-bottom: 20px; opacity: 0.5; }
            .wg-game-card-info.is-fav-mode { display: flex; justify-content: space-between; align-items: center; gap: 5px; position: relative; z-index: 10; }
            .wg-game-card-info.is-fav-mode .wg-game-card-title { margin: 0; flex: 1; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            .wg-btn-remove-fav { position: relative; z-index: 20; background: transparent; color: #a4b0be; border: none; padding: 5px; cursor: pointer; transition: color 0.2s; display: flex; align-items: center; justify-content: center; }
            .wg-btn-remove-fav:hover { color: #ff4757; }
            .wg-btn-remove-fav .dashicons { font-size: 16px; width: 16px; height: 16px; }
        </style>
        <?php
        return ob_get_clean();
    }

    /**
     * AJAX Handler for Favorite Games
     */
    public function ajax_get_favorites() {
        if ( ! isset( $_POST['ids'] ) || ! is_array( $_POST['ids'] ) ) {
            wp_send_json_error( '<div class="wg-favorite-empty"><span class="dashicons dashicons-heart"></span><p>' . __( 'You haven\'t favorited any games yet.', 'webgames' ) . '</p></div>' );
        }

        $post_ids = array_map( 'intval', $_POST['ids'] );
        $post_ids = array_filter( $post_ids ); // Remove zeros

        if ( empty( $post_ids ) ) {
            wp_send_json_error( '<div class="wg-favorite-empty"><span class="dashicons dashicons-heart"></span><p>' . __( 'You haven\'t favorited any games yet.', 'webgames' ) . '</p></div>' );
        }

        $paged = isset( $_POST['page'] ) ? max( 1, intval( $_POST['page'] ) ) : 1;
        $sort = isset( $_POST['sort'] ) ? sanitize_text_field( $_POST['sort'] ) : 'latest';
        $posts_per_page = get_option( 'posts_per_page' );

        $args = array(
            'post_type'      => 'game',
            'post_status'    => 'publish',
            'post__in'       => $post_ids,
            'posts_per_page' => $posts_per_page,
            'paged'          => $paged,
        );

        // Sorting Logic
        if ( $sort === 'hot' ) {
            $args['meta_key'] = 'game_label';
            $args['meta_value'] = 'hot';
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
        } elseif ( $sort === 'new' ) {
            $args['meta_key'] = 'game_label';
            $args['meta_value'] = 'new';
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
        } elseif ( $sort === 'latest' ) {
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
        } else {
            // default order by localStorage array
            $args['orderby'] = 'post__in';
        }

        $query = new WP_Query( $args );

        // Handle out-of-bounds pagination (e.g. deleted last item on page 2)
        if ( ! $query->have_posts() && $paged > 1 ) {
            wp_send_json_error( array( 
                'out_of_bounds' => true, 
                'redirect_page' => $paged - 1 
            ) );
        }

        ob_start();
        ?>
        <div class="wg-archive-container">
            <!-- Breadcrumbs -->
            <div class="wg-archive-breadcrumbs">
                <?php echo do_shortcode('[webgames_breadcrumbs]'); ?>
            </div>

            <!-- Header Block -->
            <div class="wg-archive-header">
                <div class="wg-archive-header-left" style="display: flex; align-items: baseline; gap: 15px; flex-wrap: wrap;">
                    <h1 class="wg-archive-title" style="margin-bottom: 0;"><?php _e( 'My Favorite Games', 'webgames' ); ?></h1>
                    <?php
                    $total_results = $query->found_posts;
                    $current_page = max( 1, $paged );
                    $first = ( $current_page - 1 ) * $posts_per_page + 1;
                    $last  = min( $total_results, $current_page * $posts_per_page );
                    
                    if ( $total_results == 1 ) {
                        $result_text = __( 'Showing the single result', 'webgames' );
                    } elseif ( $total_results <= $posts_per_page || $total_results == 0 ) {
                        /* translators: %d: total results */
                        $result_text = sprintf( __( 'Showing all %d results', 'webgames' ), $total_results );
                    } else {
                        /* translators: 1: first result 2: last result 3: total results */
                        $result_text = sprintf( __( 'Showing %1$d&ndash;%2$d of %3$d results', 'webgames' ), $first, $last, $total_results );
                    }
                    ?>
                    <span class="wg-archive-result-count" style="color: #a4b0be; font-size: 14px;">
                        <?php echo $result_text; ?>
                    </span>
                </div>
                <div class="wg-archive-header-right">
                    <form method="GET" action="" class="wg-sort-form">
                        <label for="wg-sort-select"><?php _e( 'Sort By:', 'webgames' ); ?></label>
                        <select name="sort" id="wg-sort-select">
                            <option value="default" <?php selected( $sort, 'default' ); ?>><?php _e( 'Default', 'webgames' ); ?></option>
                            <option value="latest" <?php selected( $sort, 'latest' ); ?>><?php _e( 'Latest', 'webgames' ); ?></option>
                            <option value="hot" <?php selected( $sort, 'hot' ); ?>><?php _e( 'Hot', 'webgames' ); ?></option>
                            <option value="new" <?php selected( $sort, 'new' ); ?>><?php _e( 'New', 'webgames' ); ?></option>
                        </select>
                    </form>
                </div>
            </div>

            <!-- Grid Block -->
            <div class="wg-archive-grid-wrapper">
            <?php
            if ( $query->have_posts() ) {
                $count = 0;
                while ( $query->have_posts() ) {
                    $query->the_post();

                    // Inject Ad after 4th item (index 3, so when count hits 4)
                    if ( $count === 4 ) {
                        echo '<div class="wg-archive-ad-box">';
                        // Placeholder for 2x2 Ad
                        echo '<div class="wg-ad-placeholder">Ad 4x4 (2x2 Grid)</div>';
                        echo '</div>';
                    }

                    echo $this->get_game_card_html( get_the_ID(), 'medium', 'wg-game-card', true );
                    $count++;
                }
            } else {
                echo '<div class="wg-favorite-empty" style="grid-column: 1 / -1;"><span class="dashicons dashicons-heart"></span><p>' . __( 'No games found for this filter.', 'webgames' ) . '</p></div>';
            }
            ?>
        </div>

        <!-- Pagination Block -->
        <?php
        $total_pages = $query->max_num_pages;
        if ( $total_pages > 1 ) {
            $current_page = max( 1, $paged );
            $base_url = isset($_POST['base_url']) ? esc_url_raw($_POST['base_url']) : home_url('/favorite-games/');
            $big = 999999999;
            $paginate_base = str_replace( $big, '%#%', esc_url_raw( add_query_arg( 'pg', $big, $base_url ) ) );
            
            echo '<div class="wg-pagination">';
            echo paginate_links( array(
                'base'      => $paginate_base,
                'format'    => '?pg=%#%',
                'current'   => $current_page,
                'total'     => $total_pages,
                'prev_text' => __( '&laquo; Prev', 'webgames' ),
                'next_text' => __( 'Next &raquo;', 'webgames' ),
            ) );
            echo '</div>';
        }
        
        wp_reset_postdata();
        ?>
        </div>
        <?php
        $html = ob_get_clean();
        wp_send_json_success( $html );
    }

    public function render_rating( $atts ) {
        if ( ! is_singular( 'game' ) ) {
            return '';
        }
        
        $post_id = get_the_ID();
        $rating = get_post_meta( $post_id, '_game_rating', true );
        
        // If empty, calculate it once (fallback)
        if ( $rating === '' ) {
            $real_like = (int) get_post_meta( $post_id, '_real_like', true );
            $real_dislike = (int) get_post_meta( $post_id, '_real_dislike', true );
            $fake_like = get_post_meta( $post_id, 'fake_like', true );
            $fake_like = ( $fake_like !== '' ) ? (int) $fake_like : 100;
            $fake_dislike = get_post_meta( $post_id, 'fake_dislike', true );
            $fake_dislike = ( $fake_dislike !== '' ) ? (int) $fake_dislike : 10;
            
            $total_like = $fake_like + $real_like;
            $total_dislike = $fake_dislike + $real_dislike;
            $total_votes = $total_like + $total_dislike;
            $rating = ( $total_votes > 0 ) ? round( ( $total_like / $total_votes ) * 100 ) : 0;
        }

        ob_start();
        ?>
        <div class="wg-game-rating-wrapper" style="margin-top: 5px; margin-bottom: 15px; color: #a4b0be; font-size: 14px; display: flex; align-items: center; gap: 5px;">
            <span class="dashicons dashicons-star-filled" style="color: #f1c40f;"></span>
            <span class="wg-game-rating-value" id="wg-game-rating-val"><?php echo esc_html( $rating ); ?>%</span>
            <span><?php _e( 'Like this game', 'webgames' ); ?></span>
        </div>
        <?php
        return ob_get_clean();
    }

    public function render_header_user( $atts ) {
        $google_id = get_option( 'webgames_google_client_id' );
        $facebook_id = get_option( 'webgames_facebook_app_id' );

        $callback_url = trailingslashit( site_url() ) . '?webgames_social_login=1';
        $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $state = base64_encode( json_encode( array(
            'redirect' => $current_url,
            'nonce'    => wp_create_nonce( 'wg_social_login_nonce' )
        ) ) );

        ob_start();
        ?>
        <div class="wg-header-user-wrapper wg-user-inner-wrapper" style="opacity: 0; transition: opacity 0.3s; position: relative; display: flex; align-items: center; height: 100%;">
            
            <div class="wg-header-logged-out" style="display: none; align-items: center; height: 100%;">
                <button class="wg-btn wg-btn-primary wg-btn-header-login" style="margin: 0;">
                    <?php _e( 'Login', 'webgames' ); ?>
                </button>

                <div class="wg-login-modal-overlay wg-header-login-modal" style="display: none;">
                    <div class="wg-login-modal">
                        <button class="wg-btn-close-modal"><span class="dashicons dashicons-no-alt"></span></button>
                        <h3 class="wg-login-modal-title"><?php _e( 'Login to Play', 'webgames' ); ?></h3>
                        <p class="wg-login-modal-subtitle"><?php _e( 'Choose a social account to continue', 'webgames' ); ?></p>
                        
                        <div class="wg-social-login-buttons">
                        <?php if ( ! empty( $google_id ) ) : 
                            $google_auth_url = add_query_arg( array(
                                'client_id'     => $google_id,
                                'redirect_uri'  => urlencode( $callback_url . '&provider=google' ),
                                'response_type' => 'code',
                                'scope'         => 'email profile',
                                'state'         => $state,
                            ), 'https://accounts.google.com/o/oauth2/v2/auth' );
                        ?>
                            <a href="<?php echo esc_url( $google_auth_url ); ?>" class="wg-btn-social wg-btn-google">
                                <svg width="18" height="18" viewBox="0 0 48 48"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/></svg>
                                <span><?php _e( 'Continue with Google', 'webgames' ); ?></span>
                            </a>
                        <?php endif; ?>

                        <?php if ( ! empty( $facebook_id ) ) : 
                            $fb_auth_url = add_query_arg( array(
                                'client_id'     => $facebook_id,
                                'redirect_uri'  => urlencode( $callback_url . '&provider=facebook' ),
                                'state'         => $state,
                                'scope'         => 'email,public_profile',
                            ), 'https://www.facebook.com/v19.0/dialog/oauth' );
                        ?>
                            <a href="<?php echo esc_url( $fb_auth_url ); ?>" class="wg-btn-social wg-btn-facebook">
                                <svg width="18" height="18" viewBox="0 0 320 512"><path fill="#ffffff" d="M279.14 288l14.22-92.66h-88.91v-60.13c0-25.35 12.42-50.06 52.24-50.06h40.42V6.26S260.43 0 225.36 0c-73.22 0-121.08 44.38-121.08 124.72v70.62H22.89V288h81.39v224h100.17V288z"/></svg>
                                <span><?php _e( 'Continue with Facebook', 'webgames' ); ?></span>
                            </a>
                        <?php endif; ?>
                        <?php if ( empty( $google_id ) && empty( $facebook_id ) ) : ?>
                            <a href="<?php echo esc_url( wp_login_url( $current_url ) ); ?>" class="wg-btn wg-btn-primary wg-btn-normal-login"><?php _e('Normal Login', 'webgames'); ?></a>
                        <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="wg-header-logged-in" style="display: none; position: relative; align-items: center; height: 100%;">
                <button class="wg-header-profile-btn" style="margin: 0;">
                    <img class="wg-header-avatar" src="" alt="Avatar" width="24" height="24">
                    <span class="wg-header-name"></span>
                    <span class="dashicons dashicons-arrow-down-alt2 wg-header-profile-dropdown-icon"></span>
                </button>
                <div class="wg-header-profile-menu" style="display:none;">
                    <a href="<?php echo esc_url( wp_logout_url( $current_url ) ); ?>" class="wg-logout-link">
                        <span class="dashicons dashicons-external"></span> <?php _e( 'Logout', 'webgames' ); ?>
                    </a>
                </div>
            </div>
            
        </div>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var wrappers = document.querySelectorAll('.wg-user-inner-wrapper');
            if (wrappers.length === 0) return;

            var getCookie = function(name) {
                var match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
                if (match) return match[2];
                return null;
            };

            var userInfo = null;
            var userInfoBase64 = getCookie('wg_user_info');
            if (userInfoBase64) {
                try {
                    var userInfoStr = atob(decodeURIComponent(userInfoBase64));
                    userInfo = JSON.parse(userInfoStr);
                } catch(e) {}
            }

            wrappers.forEach(function(wrapper) {
                var loggedOut = wrapper.querySelector('.wg-header-logged-out');
                var loggedIn = wrapper.querySelector('.wg-header-logged-in');
                
                if (userInfo && userInfo.name) {
                    var nameEl = wrapper.querySelector('.wg-header-name');
                    var avatarEl = wrapper.querySelector('.wg-header-avatar');
                    if (nameEl) nameEl.textContent = userInfo.name;
                    if (avatarEl) {
                        if (userInfo.avatar) {
                            avatarEl.src = userInfo.avatar;
                        } else {
                            avatarEl.style.display = 'none';
                        }
                    }
                    if (loggedIn) loggedIn.style.display = 'flex';
                } else {
                    if (loggedOut) loggedOut.style.display = 'flex';
                }
                wrapper.style.opacity = '1';
                var btnLogin = wrapper.querySelector('.wg-btn-header-login');
                var modal = wrapper.querySelector('.wg-header-login-modal');
                var btnClose = wrapper.querySelector('.wg-btn-close-modal');
                if (btnLogin && modal) {
                    btnLogin.addEventListener('click', function() {
                        modal.style.display = 'flex';
                    });
                    if (btnClose) {
                        btnClose.addEventListener('click', function() {
                            modal.style.display = 'none';
                        });
                    }
                    modal.addEventListener('click', function(e) {
                        if (e.target === modal) {
                            modal.style.display = 'none';
                        }
                    });
                }
                var profileBtn = wrapper.querySelector('.wg-header-profile-btn');
                var profileMenu = wrapper.querySelector('.wg-header-profile-menu');
                if (profileBtn && profileMenu) {
                    profileBtn.addEventListener('click', function(e) {
                        e.stopPropagation();
                        profileMenu.style.display = profileMenu.style.display === 'none' ? 'block' : 'none';
                    });
                }
            });
            document.addEventListener('click', function() {
                var menus = document.querySelectorAll('.wg-header-profile-menu');
                menus.forEach(function(menu) {
                    menu.style.display = 'none';
                });
            });
        });
        </script>
        <?php
        $html = ob_get_clean();
        $html = str_replace( array( "\r\n", "\r", "\n", "\t" ), '', $html );
        return $html;
    }

    public function recently_played_shortcode( $atts ) {
        ob_start();
        ?>
        <div id="wg-recently-played-container" style="min-height: 500px;" class="wg-fade-content">
            <div class="wg-archive-container wg-skeleton-container">
                <div class="wg-archive-header" style="height: 40px; margin-bottom: 20px;">
                    <div class="wg-skeleton-pulse" style="width: 250px; height: 32px; border-radius: 5px;"></div>
                </div>
                <div class="wg-archive-grid-wrapper">
                    <?php for ( $i = 0; $i < 12; $i++ ) : ?>
                    <div class="wg-game-card" style="background: transparent; box-shadow: none;">
                        <div class="wg-sidebar-thumb-container wg-skeleton-pulse" style="padding-bottom: 75%; border-radius: 12px; height: 0;"></div>
                        <div class="wg-game-card-info" style="padding: 10px 0;">
                            <div class="wg-skeleton-pulse" style="width: <?php echo rand(60, 90); ?>%; height: 20px; border-radius: 4px;"></div>
                        </div>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
        <style>
            .wg-fade-content { transition: opacity 0.3s ease; }
            .wg-fade-out { opacity: 0.3; pointer-events: none; }
            .wg-skeleton-pulse {
                background-color: #f0f0f0;
                animation: wg-skeleton-loading 1.5s infinite ease-in-out;
            }
            body.dark-mode .wg-skeleton-pulse, .dark-mode .wg-skeleton-pulse {
                background-color: rgba(255,255,255,0.05);
                animation: wg-skeleton-loading-dark 1.5s infinite ease-in-out;
            }
            @keyframes wg-skeleton-loading {
                0% { background-color: #f0f0f0; }
                50% { background-color: #e0e0e0; }
                100% { background-color: #f0f0f0; }
            }
            @keyframes wg-skeleton-loading-dark {
                0% { background-color: rgba(255,255,255,0.05); }
                50% { background-color: rgba(255,255,255,0.1); }
                100% { background-color: rgba(255,255,255,0.05); }
            }
            .wg-recent-empty { text-align: center; color: #a4b0be; padding: 60px 0; font-size: 16px; }
            .wg-recent-empty .dashicons { font-size: 60px; width: 60px; height: 60px; color: #a4b0be; margin-bottom: 20px; opacity: 0.5; }
        </style>
        <?php
        return ob_get_clean();
    }

    public function ajax_get_recently_played() {
        if ( ! isset( $_POST['ids'] ) || ! is_array( $_POST['ids'] ) ) {
            wp_send_json_error( '<div class="wg-recent-empty"><span class="dashicons dashicons-backup"></span><p>' . __( 'You haven\'t played any games yet.', 'webgames' ) . '</p></div>' );
        }

        $post_ids = array_map( 'intval', $_POST['ids'] );
        $post_ids = array_filter( $post_ids );

        if ( empty( $post_ids ) ) {
            wp_send_json_error( '<div class="wg-recent-empty"><span class="dashicons dashicons-backup"></span><p>' . __( 'You haven\'t played any games yet.', 'webgames' ) . '</p></div>' );
        }

        $paged = isset( $_POST['page'] ) ? max( 1, intval( $_POST['page'] ) ) : 1;
        $sort = isset( $_POST['sort'] ) ? sanitize_text_field( $_POST['sort'] ) : 'latest';
        $layout = isset( $_POST['layout'] ) ? sanitize_text_field( $_POST['layout'] ) : 'grid';
        $posts_per_page = get_option( 'posts_per_page' );
        
        // Override posts per page for slider
        if ( $layout === 'slider' ) {
            $posts_per_page = 12;
            $paged = 1;
        }

        $args = array(
            'post_type'      => 'game',
            'post_status'    => 'publish',
            'post__in'       => $post_ids,
            'posts_per_page' => $posts_per_page,
            'paged'          => $paged,
        );

        // Sorting Logic
        if ( $sort === 'hot' ) {
            $args['meta_key'] = 'game_label';
            $args['meta_value'] = 'hot';
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
        } elseif ( $sort === 'new' ) {
            $args['meta_key'] = 'game_label';
            $args['meta_value'] = 'new';
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
        } elseif ( $sort === 'latest' ) {
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
        } else {
            // default order by localStorage array
            $args['orderby'] = 'post__in';
        }

        $query = new WP_Query( $args );

        // Handle out-of-bounds pagination
        if ( ! $query->have_posts() && $paged > 1 ) {
            wp_send_json_error( array( 
                'out_of_bounds' => true, 
                'redirect_page' => $paged - 1 
            ) );
        }

        ob_start();
        
        if ( $layout === 'slider' ) {
            if ( $query->have_posts() ) {
                while ( $query->have_posts() ) {
                    $query->the_post();
                    echo $this->get_game_card_html( get_the_ID(), 'medium', 'wg-game-card' );
                }
            }
            wp_reset_postdata();
            $html = ob_get_clean();
            wp_send_json_success( $html );
            return;
        }

        ?>
        <div class="wg-archive-container">
            <!-- Breadcrumbs -->
            <div class="wg-archive-breadcrumbs">
                <?php echo do_shortcode('[webgames_breadcrumbs]'); ?>
            </div>

            <!-- Header Block -->
            <div class="wg-archive-header">
                <div class="wg-archive-header-left" style="display: flex; align-items: baseline; gap: 15px; flex-wrap: wrap;">
                    <h1 class="wg-archive-title" style="margin-bottom: 0;"><?php _e( 'Recently Played', 'webgames' ); ?></h1>
                    <?php
                    $total_results = $query->found_posts;
                    $current_page = max( 1, $paged );
                    $first = ( $current_page - 1 ) * $posts_per_page + 1;
                    $last  = min( $total_results, $current_page * $posts_per_page );
                    
                    if ( $total_results == 1 ) {
                        $result_text = __( 'Showing the single result', 'webgames' );
                    } elseif ( $total_results <= $posts_per_page || $total_results == 0 ) {
                        /* translators: %d: total results */
                        $result_text = sprintf( __( 'Showing all %d results', 'webgames' ), $total_results );
                    } else {
                        /* translators: 1: first result 2: last result 3: total results */
                        $result_text = sprintf( __( 'Showing %1$d&ndash;%2$d of %3$d results', 'webgames' ), $first, $last, $total_results );
                    }
                    ?>
                    <span class="wg-archive-result-count" style="color: #a4b0be; font-size: 14px;">
                        <?php echo $result_text; ?>
                    </span>
                </div>
                <div class="wg-archive-header-right">
                    <form method="GET" action="" class="wg-sort-form">
                        <label for="wg-sort-select"><?php _e( 'Sort By:', 'webgames' ); ?></label>
                        <select name="sort" id="wg-sort-select">
                            <option value="default" <?php selected( $sort, 'default' ); ?>><?php _e( 'Default', 'webgames' ); ?></option>
                            <option value="latest" <?php selected( $sort, 'latest' ); ?>><?php _e( 'Latest', 'webgames' ); ?></option>
                            <option value="hot" <?php selected( $sort, 'hot' ); ?>><?php _e( 'Hot', 'webgames' ); ?></option>
                            <option value="new" <?php selected( $sort, 'new' ); ?>><?php _e( 'New', 'webgames' ); ?></option>
                        </select>
                    </form>
                </div>
            </div>

            <!-- Grid Block -->
            <div class="wg-archive-grid-wrapper">
            <?php
            if ( $query->have_posts() ) {
                $count = 0;
                while ( $query->have_posts() ) {
                    $query->the_post();

                    // Inject Ad after 4th item (index 3, so when count hits 4)
                    if ( $count === 4 ) {
                        echo '<div class="wg-archive-ad-box">';
                        // Placeholder for 2x2 Ad
                        echo '<div class="wg-ad-placeholder">Ad 4x4 (2x2 Grid)</div>';
                        echo '</div>';
                    }

                    echo $this->get_game_card_html( get_the_ID(), 'medium', 'wg-game-card' );
                    $count++;
                }
            } else {
                echo '<div class="wg-recent-empty" style="grid-column: 1 / -1;"><span class="dashicons dashicons-backup"></span><p>' . __( 'No games found for this filter.', 'webgames' ) . '</p></div>';
            }
            ?>
            </div>

            <!-- Pagination Block -->
            <?php
            $total_pages = $query->max_num_pages;
            if ( $total_pages > 1 ) {
                $current_page = max( 1, $paged );
                $base_url = isset($_POST['base_url']) ? esc_url_raw($_POST['base_url']) : home_url('/recently-played/');
                $big = 999999999;
                $paginate_base = str_replace( $big, '%#%', esc_url_raw( add_query_arg( 'pg', $big, $base_url ) ) );
                
                echo '<div class="wg-pagination">';
                echo paginate_links( array(
                    'base'      => $paginate_base,
                    'format'    => '?pg=%#%',
                    'current'   => $current_page,
                    'total'     => $total_pages,
                    'prev_text' => __( '&laquo; Prev', 'webgames' ),
                    'next_text' => __( 'Next &raquo;', 'webgames' ),
                ) );
                echo '</div>';
            }
            
            wp_reset_postdata();
            ?>
        </div>
        <?php
        wp_send_json_success( ob_get_clean() );
        wp_die();
    }


    /**
     * Shortcode to output automatic Discover Menu
     */
    public function game_slider_shortcode( $atts ) {
        $atts = shortcode_atts( array(
            'title' => 'Games',
            'icon' => 'dashicons-games',
            'sort' => 'popular',
            'category_slug' => '',
            'count' => 12,
        ), $atts, 'webgames_game_slider' );

        $title = esc_html( $atts['title'] );
        $icon = esc_attr( $atts['icon'] );
        $sort = sanitize_text_field( $atts['sort'] );
        $category_slug = sanitize_text_field( $atts['category_slug'] );
        $count = intval( $atts['count'] );

        // Auto-calculate view_more_link
        $archive_link = get_post_type_archive_link( 'game' );
        $view_more_link = '';
        if ( $sort === 'popular' || $sort === 'new' || $sort === 'hot' || $sort === 'rating' ) {
            $view_more_link = add_query_arg( 'sort', $sort, $archive_link );
        } elseif ( $sort === 'recent' ) {
            $view_more_link = home_url( '/recently-played' );
        } elseif ( $sort === 'category' && ! empty( $category_slug ) ) {
            $term_link = get_term_link( $category_slug, 'game-category' );
            if ( ! is_wp_error( $term_link ) ) {
                $view_more_link = $term_link;
            }
        }

        ob_start();
        $slider_id = 'wg-slider-' . uniqid();

        // If 'recent', output shell for JS
        if ( $sort === 'recent' ) {
            ?>
            <div class="wg-slider-section wg-recent-slider-section" id="<?php echo esc_attr( $slider_id ); ?>-section" style="display: none;">
                <div class="wg-section-header">
                    <h2 class="wg-section-title"><span class="dashicons <?php echo esc_attr( $icon ); ?>"></span> <?php echo $title; ?></h2>
                    <?php if ( $view_more_link ) : ?>
                    <a href="<?php echo esc_url( $view_more_link ); ?>" class="wg-view-more"><?php _e('View more', 'webgames'); ?> <span class="dashicons dashicons-arrow-right-alt2"></span></a>
                    <?php endif; ?>
                </div>
                <div class="wg-game-slider" id="<?php echo esc_attr( $slider_id ); ?>-container"></div>
            </div>
            <?php
            $html = ob_get_clean();
            return str_replace( array( "\r\n", "\r", "\n", "\t" ), '', $html );
        }

        // WP Query for others
        $args = array(
            'post_type' => 'game',
            'post_status' => 'publish',
            'posts_per_page' => $count,
        );

        if ( $sort === 'popular' ) {
            $args['meta_key'] = 'wg_views';
            $args['orderby'] = 'meta_value_num';
            $args['order'] = 'DESC';
        } elseif ( $sort === 'new' ) {
            $args['meta_key'] = 'game_label';
            $args['meta_value'] = 'new';
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
        } elseif ( $sort === 'hot' ) {
            $args['meta_key'] = 'game_label';
            $args['meta_value'] = 'hot';
            $args['orderby'] = 'date';
            $args['order'] = 'DESC';
        } elseif ( $sort === 'rating' ) {
            $args['meta_key'] = '_game_rating';
            $args['orderby'] = 'meta_value_num';
            $args['order'] = 'DESC';
        } elseif ( $sort === 'category' && ! empty( $category_slug ) ) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'game-category',
                    'field'    => 'slug',
                    'terms'    => $category_slug,
                ),
            );
        } else {
            $args['orderby'] = 'date';
        }

        $query = new WP_Query( $args );

        if ( ! $query->have_posts() ) {
            return '';
        }
        ?>
        <div class="wg-slider-section">
            <div class="wg-section-header">
                <h2 class="wg-section-title"><span class="dashicons <?php echo esc_attr( $icon ); ?>"></span> <?php echo $title; ?></h2>
                <?php if ( $view_more_link ) : ?>
                <a href="<?php echo esc_url( $view_more_link ); ?>" class="wg-view-more"><?php _e('View more', 'webgames'); ?> <span class="dashicons dashicons-arrow-right-alt2"></span></a>
                <?php endif; ?>
            </div>
            <div class="wg-game-slider">
                <?php
                while ( $query->have_posts() ) {
                    $query->the_post();
                    echo $this->get_game_card_html( get_the_ID(), 'medium', 'wg-game-card' );
                }
                wp_reset_postdata();
                ?>
            </div>
        </div>
        <?php
        $html = ob_get_clean();
        return str_replace( array( "\r\n", "\r", "\n", "\t" ), '', $html );
    }

    public function discover_menu_shortcode( $atts ) {
        $archive_link = get_post_type_archive_link('game');
        // Fallback if no archive link configured
        if ( ! $archive_link ) {
            $archive_link = home_url('/?post_type=game');
        }

        ob_start();
        ?>
        <ul class="wg-cat-menu wg-discover-menu" style="margin-bottom: 20px;">
            <li>
                <a href="<?php echo esc_url( home_url('/') ); ?>">
                    <span class="wg-cat-icon-wrapper"><span class="dashicons dashicons-admin-home"></span></span><span class="wg-cat-text"><?php _e('Home', 'webgames'); ?></span>
                </a>
            </li>
            <li>
                <a href="<?php echo esc_url( add_query_arg('sort', 'new', $archive_link) ); ?>">
                    <span class="wg-cat-icon-wrapper"><span class="dashicons dashicons-star-filled"></span></span><span class="wg-cat-text"><?php _e('New', 'webgames'); ?></span>
                </a>
            </li>
            <li>
                <a href="<?php echo esc_url( add_query_arg('sort', 'hot', $archive_link) ); ?>">
                    <span class="wg-cat-icon-wrapper"><span class="dashicons dashicons-megaphone"></span></span><span class="wg-cat-text"><?php _e('Hot', 'webgames'); ?></span>
                </a>
            </li>
            <li>
                <a href="<?php echo esc_url( add_query_arg('sort', 'popular', $archive_link) ); ?>">
                    <span class="wg-cat-icon-wrapper"><span class="dashicons dashicons-chart-bar"></span></span><span class="wg-cat-text"><?php _e('Most Played', 'webgames'); ?></span>
                </a>
            </li>
            <li>
                <a href="<?php echo esc_url( add_query_arg('sort', 'rating', $archive_link) ); ?>">
                    <span class="wg-cat-icon-wrapper"><span class="dashicons dashicons-awards"></span></span><span class="wg-cat-text"><?php _e('Best Rating', 'webgames'); ?></span>
                </a>
            </li>
        </ul>
        <?php
        $html = ob_get_clean();
        return str_replace( array( "\r\n", "\r", "\n", "\t" ), '', $html );
    }

    /**
     * Filter FSE Blocks to replace [icon:name] with Dashicons
     */
    public function filter_nav_link_icon( $block_content, $block ) {
        $content = $this->replace_icon_tags( $block_content );
        // Move the icon wrapper outside the label span so it remains visible when the label is hidden
        $pattern = '/(<span[^>]*class="[^"]*wp-block-navigation-item__label[^"]*"[^>]*>)\s*(<span class="wg-cat-icon-wrapper">.*?<\/span><\/span>)\s*/is';
        $content = preg_replace( $pattern, '$2$1', $content );
        return $content;
    }

    public function filter_content_icon( $content ) {
        // Prevent applying on admin area unless it's an ajax call, but usually safe on frontend.
        if ( is_admin() && ! wp_doing_ajax() ) {
            return $content;
        }
        return $this->replace_icon_tags( $content );
    }

    private function replace_icon_tags( $content ) {
        // Match [icon:dashicon-name] or [icon:name]
        $pattern = '/\[icon:([a-zA-Z0-9\-]+)\]/i';
        $content = preg_replace_callback( $pattern, function( $matches ) {
            $icon_name = sanitize_html_class( $matches[1] );
            // Dashicons typically start with dashicons- prefix, if user didn't add it, add it automatically
            if ( strpos( $icon_name, 'dashicons-' ) !== 0 ) {
                $icon_class = 'dashicons-' . $icon_name;
            } else {
                $icon_class = $icon_name;
            }
            return '<span class="wg-cat-icon-wrapper"><span class="dashicons ' . esc_attr( $icon_class ) . '"></span></span>';
        }, $content );
        return $content;
    }

    /**
     * Shortcode to output All Tags alphabetically
     */
    public function all_tags_shortcode( $atts ) {
        $terms = get_terms( array(
            'taxonomy' => 'game-tag',
            'hide_empty' => true,
        ) );

        if ( is_wp_error( $terms ) || empty( $terms ) ) {
            return '<p>' . __( 'No tags found.', 'webgames' ) . '</p>';
        }

        $grouped_terms = array();
        foreach ( $terms as $term ) {
            $first_char = mb_strtoupper( mb_substr( $term->name, 0, 1 ) );
            // Group non-alphabet characters into '#'
            if ( ! preg_match( '/[A-Z]/', $first_char ) ) {
                $first_char = '#';
            }
            $grouped_terms[ $first_char ][] = $term;
        }

        ksort( $grouped_terms );
        // Move '#' to the beginning if exists
        if ( isset( $grouped_terms['#'] ) ) {
            $hash_group = $grouped_terms['#'];
            unset( $grouped_terms['#'] );
            $grouped_terms = array( '#' => $hash_group ) + $grouped_terms;
        }

        ob_start();
        ?>
        <div class="wg-all-tags-container">
            <h1 class="wg-archive-title" style="margin-bottom: 20px;"><?php _e( 'All Tags', 'webgames' ); ?></h1>
            
            <div class="wg-tags-nav">
                <?php foreach ( $grouped_terms as $letter => $group ) : ?>
                    <a href="#tag-group-<?php echo esc_attr( $letter === '#' ? 'num' : $letter ); ?>"><?php echo esc_html( $letter ); ?></a>
                <?php endforeach; ?>
            </div>

            <div class="wg-tags-content">
                <?php foreach ( $grouped_terms as $letter => $group ) : ?>
                    <div id="tag-group-<?php echo esc_attr( $letter === '#' ? 'num' : $letter ); ?>" class="wg-tag-group">
                        <h2 class="wg-tag-letter"><?php echo esc_html( $letter ); ?></h2>
                        <ul class="wg-tag-grid">
                            <?php foreach ( $group as $term ) : 
                                $image_id = get_term_meta( $term->term_id, 'wg_category_image', true );
                                $icon_class = get_term_meta( $term->term_id, 'wg_category_icon_class', true );
                                
                                $icon_html = '';
                                if ( $image_id ) {
                                    $image_url = wp_get_attachment_image_url( $image_id, 'thumbnail' );
                                    if ( $image_url ) {
                                        $icon_html = '<img src="' . esc_url( $image_url ) . '" alt="' . esc_attr( $term->name ) . '" class="wg-tag-icon-img" style="width:24px; height:24px; vertical-align:middle; margin-right:8px; border-radius:4px; object-fit: cover;" />';
                                    }
                                }
                                
                                if ( ! $icon_html ) {
                                    if ( $icon_class ) {
                                        $icon_html = '<span class="dashicons ' . esc_attr( $icon_class ) . '" style="margin-right:8px; display:flex; align-items:center;"></span>';
                                    } else {
                                        $icon_html = '<span class="dashicons dashicons-tag" style="margin-right:8px; display:flex; align-items:center;"></span>';
                                    }
                                }
                            ?>
                                <li>
                                    <a href="<?php echo esc_url( get_term_link( $term ) ); ?>">
                                        <div class="wg-tag-name-wrap" style="display:flex; align-items:center;">
                                            <?php echo $icon_html; ?>
                                            <?php echo esc_html( $term->name ); ?> 
                                        </div>
                                        <span class="wg-tag-count">(<?php echo esc_html( $term->count ); ?>)</span>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
    public function home_ad_shortcode() {
        $ad_code = get_option( 'webgames_home_ad_code' );
        if ( empty( $ad_code ) ) {
            return '';
        }
        ob_start();
        ?>
        <div class="wg-home-ad-wrapper" style="margin-bottom: 20px; display: flex; justify-content: center; overflow: hidden; border-radius: 12px; background: #111418; padding: 10px;">
            <?php echo do_shortcode( $ad_code ); ?>
        </div>
        <?php
        $html = ob_get_clean();
        return str_replace( array( "\r\n", "\r", "\n", "\t" ), '', $html );
    }
}
