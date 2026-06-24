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
        add_shortcode( 'webgames_rating', array( $this, 'render_rating' ) );
    }

    public function register_assets() {
        wp_enqueue_style( 'dashicons' );
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
            <div class="webgames-canvas-container">
                <div class="webgames-cover" id="webgames-cover" style="background-image: url('<?php echo esc_url( $cover_url ); ?>');">
                    <button class="webgames-play-btn" id="webgames-play-btn" data-src="<?php echo esc_url( $game_url ); ?>">
                        <span class="dashicons dashicons-controls-play"></span> <?php _e( 'PLAY NOW', 'webgames' ); ?>
                    </button>
                </div>
                <iframe id="webgames-iframe" class="webgames-iframe" src="" frameborder="0" scrolling="no" allowfullscreen></iframe>
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
                    <span class="wg-title"><?php echo esc_html( $title ); ?></span>
                </div>
                <div class="wg-toolbar-right">
                    <button class="wg-btn wg-btn-like" data-post-id="<?php echo esc_attr( $post_id ); ?>" data-tooltip="<?php _e('Like', 'webgames'); ?>">
                        <span class="dashicons dashicons-thumbs-up"></span> <span class="wg-count-like"><?php echo esc_html( $total_like ); ?></span>
                    </button>
                    <button class="wg-btn wg-btn-dislike" data-post-id="<?php echo esc_attr( $post_id ); ?>" data-tooltip="<?php _e('Dislike', 'webgames'); ?>">
                        <span class="dashicons dashicons-thumbs-down"></span> <span class="wg-count-dislike"><?php echo esc_html( $total_dislike ); ?></span>
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
                    <button id="wg-report-close" class="wg-btn wg-btn-close">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
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
        return ob_get_clean();
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
            echo '<li><a href="' . esc_url( $link ) . '"><span class="dashicons dashicons-category"></span> ' . esc_html( $term->name ) . '</a></li>';
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
        if ( ! is_singular( 'game' ) ) {
            return '';
        }
        
        $separator = ' <span class="wg-breadcrumb-sep">/</span> ';
        $home = '<a href="' . home_url() . '" class="wg-breadcrumb-home"><span class="dashicons dashicons-admin-home"></span> ' . esc_html__( 'Home', 'webgames' ) . '</a>';
        
        $terms = get_the_terms( get_the_ID(), 'game-category' );
        $cat_link = '';
        if ( $terms && ! is_wp_error( $terms ) ) {
            $term = $terms[0]; // Get the first category
            $cat_link = '<a href="' . esc_url( get_term_link( $term ) ) . '" class="wg-breadcrumb-cat">' . esc_html( $term->name ) . '</a>';
        }
        
        $title = '<span class="wg-breadcrumb-current">' . esc_html( get_the_title() ) . '</span>';
        
        $html = '<div class="wg-breadcrumbs">';
        $html .= $home;
        if ( $cat_link ) {
            $html .= $separator . $cat_link;
        }
        $html .= $separator . $title;
        $html .= '</div>';
        
        return $html;
    }

    private function get_game_card_html( $post_id, $image_size = 'medium', $item_class = 'wg-game-card' ) {
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
        
        $html .= '<div class="wg-game-card-info">';
        $html .= '<h3 class="wg-game-card-title">';
        $html .= '<a href="' . $permalink . '" class="wg-game-card-link">' . $title . '</a>';
        $html .= '</h3>';
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

        if ( get_query_var( 'paged' ) ) {
            $paged = get_query_var( 'paged' );
        } elseif ( get_query_var( 'page' ) ) {
            $paged = get_query_var( 'page' );
        } else {
            $paged = 1;
        }
        
        $sort = isset( $_GET['sort'] ) ? sanitize_text_field( $_GET['sort'] ) : 'latest';
        
        $args = array(
            'post_type'      => 'game',
            'post_status'    => 'publish',
            'posts_per_page' => get_option( 'posts_per_page' ),
            'paged'          => $paged,
        );

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
        } elseif ( is_post_type_archive('game') ) {
            $title = __( 'All Games', 'webgames' );
            $description = '';
            $bottom_seo_content = '';
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
                    <form method="GET" action="" class="wg-sort-form">
                        <label for="wg-sort-select"><?php _e( 'Sort By:', 'webgames' ); ?></label>
                        <select name="sort" id="wg-sort-select" onchange="this.form.submit()">
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
                    echo paginate_links( array(
                        'base'      => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
                        'format'    => '?paged=%#%',
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
}
