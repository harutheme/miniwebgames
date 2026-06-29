<?php
/**
 * Twenty Web-Games functions and definitions.
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package WordPress
 * @subpackage Twenty_Twenty_Five
 * @since Twenty Web-Games 1.0
 */

if ( ! function_exists( 'webgames_post_format_setup' ) ) :
	/**
	 * Adds theme support for post formats.
	 *
	 * @since Twenty Web-Games 1.0
	 *
	 * @return void
	 */
	function webgames_post_format_setup() {
		add_theme_support( 'post-formats', array( 'aside', 'audio', 'chat', 'gallery', 'image', 'link', 'quote', 'status', 'video' ) );
	}
endif;
add_action( 'after_setup_theme', 'webgames_post_format_setup' );

if ( ! function_exists( 'webgames_editor_style' ) ) :
	/**
	 * Enqueues editor-style.css in the editors.
	 *
	 * @since Twenty Web-Games 1.0
	 *
	 * @return void
	 */
	function webgames_editor_style() {
		add_editor_style( 'assets/css/editor-style.css' );
	}
endif;
add_action( 'after_setup_theme', 'webgames_editor_style' );

if ( ! function_exists( 'webgames_enqueue_styles' ) ) :
	/**
	 * Enqueues the theme stylesheet on the front.
	 *
	 * @since Twenty Web-Games 1.0
	 *
	 * @return void
	 */
	function webgames_enqueue_styles() {
		$suffix = SCRIPT_DEBUG ? '' : '.min';
		$src    = 'style' . $suffix . '.css';

		wp_enqueue_style(
			'webgames-style',
			get_parent_theme_file_uri( $src ),
			array(),
			wp_get_theme()->get( 'Version' )
		);
		wp_style_add_data(
			'webgames-style',
			'path',
			get_parent_theme_file_path( $src )
		);
	}
endif;
add_action( 'wp_enqueue_scripts', 'webgames_enqueue_styles' );

if ( ! function_exists( 'webgames_block_styles' ) ) :
	/**
	 * Registers custom block styles.
	 *
	 * @since Twenty Web-Games 1.0
	 *
	 * @return void
	 */
	function webgames_block_styles() {
		register_block_style(
			'core/list',
			array(
				'name'         => 'checkmark-list',
				'label'        => __( 'Checkmark', 'webgames' ),
				'inline_style' => '
				ul.is-style-checkmark-list {
					list-style-type: "\2713";
				}

				ul.is-style-checkmark-list li {
					padding-inline-start: 1ch;
				}',
			)
		);
	}
endif;
add_action( 'init', 'webgames_block_styles' );

if ( ! function_exists( 'webgames_pattern_categories' ) ) :
	/**
	 * Registers pattern categories.
	 *
	 * @since Twenty Web-Games 1.0
	 *
	 * @return void
	 */
	function webgames_pattern_categories() {

		register_block_pattern_category(
			'webgames_page',
			array(
				'label'       => __( 'Pages', 'webgames' ),
				'description' => __( 'A collection of full page layouts.', 'webgames' ),
			)
		);

		register_block_pattern_category(
			'webgames_post-format',
			array(
				'label'       => __( 'Post formats', 'webgames' ),
				'description' => __( 'A collection of post format patterns.', 'webgames' ),
			)
		);
	}
endif;
add_action( 'init', 'webgames_pattern_categories' );

if ( ! function_exists( 'webgames_register_block_bindings' ) ) :
	/**
	 * Registers the post format block binding source.
	 *
	 * @since Twenty Web-Games 1.0
	 *
	 * @return void
	 */
	function webgames_register_block_bindings() {
		register_block_bindings_source(
			'webgames/format',
			array(
				'label'              => _x( 'Post format name', 'Label for the block binding placeholder in the editor', 'webgames' ),
				'get_value_callback' => 'webgames_format_binding',
			)
		);
	}
endif;
add_action( 'init', 'webgames_register_block_bindings' );

if ( ! function_exists( 'webgames_format_binding' ) ) :
	/**
	 * Callback function for the post format name block binding source.
	 *
	 * @since Twenty Web-Games 1.0
	 *
	 * @return string|void Post format name, or nothing if the format is 'standard'.
	 */
	function webgames_format_binding() {
		$post_format_slug = get_post_format();

		if ( $post_format_slug && 'standard' !== $post_format_slug ) {
			return get_post_format_string( $post_format_slug );
		}
	}
endif;

// Temporary FSE recovery script
add_action('init', function() {
    if (isset($_GET['clear_fse'])) {
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_theme_%' OR option_name LIKE '_transient_timeout_theme_%' OR option_name LIKE '_transient_wp_core_block_css%'");
        
        $corrupted = array('sidebar-vertical', 'header', 'footer');
        foreach ($corrupted as $slug) {
            $post = get_page_by_path($slug, OBJECT, 'wp_template_part');
            if ($post) {
                wp_delete_post($post->ID, true);
            }
        }
        
        die('<h1>FSE Fixed!</h1><p>Cache Cleared and Corrupted DB Parts Deleted!</p><p>Please close this tab and go back to your Site Editor and refresh.</p>');
    }
    
    // Fix HTTP/HTTPS REST API Nonce mismatch
    if ( is_admin() && current_user_can('manage_options') ) {
        $site_url = get_option('siteurl');
        if (strpos($site_url, 'http://') === 0 && isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            update_option('siteurl', str_replace('http://', 'https://', $site_url));
            update_option('home', str_replace('http://', 'https://', get_option('home')));
        }
    }
});

// Game Sorting Logic
add_action('pre_get_posts', function($query) {
    if ( is_admin() || ! $query->is_main_query() ) return;

    if ( $query->get('post_type') === 'game' || is_tax('game_cat') || is_tax('game_tag') || is_post_type_archive('game') ) {
        $sort = isset($_GET['sort']) ? sanitize_text_field($_GET['sort']) : '';
        
        switch ($sort) {
            case 'popular':
                $query->set('meta_key', 'wg_views');
                $query->set('orderby', 'meta_value_num');
                $query->set('order', 'DESC');
                break;
            case 'hot':
                $query->set('meta_key', 'wg_views');
                $query->set('orderby', 'meta_value_num');
                $query->set('order', 'DESC');
                $query->set('date_query', array(
                    array('after' => '30 days ago')
                ));
                break;
            case 'rating':
                $query->set('meta_key', '_game_rating');
                $query->set('orderby', 'meta_value_num');
                $query->set('order', 'DESC');
                break;
            case 'new':
                $query->set('orderby', 'date');
                $query->set('order', 'DESC');
                break;
        }
    }
});

// Conditionally hide site title if custom logo exists
add_filter( 'render_block_core/site-title', function( $block_content, $block ) {
    if ( ! is_admin() && ! wp_is_json_request() ) {
        if ( has_custom_logo() ) {
            return '';
        }
    }
    return $block_content;
}, 10, 2 );

// Gỡ bỏ wpautop trên toàn bộ site để ngăn chặn việc WordPress tự sinh thẻ <p> rác
add_action( 'init', 'webgames_disable_wpautop_globally', 10 );
function webgames_disable_wpautop_globally() {
    // Gỡ bỏ wpautop khỏi tất cả các bộ lọc cốt lõi
    remove_filter( 'the_content', 'wpautop' );
    remove_filter( 'the_excerpt', 'wpautop' );
    remove_filter( 'widget_text_content', 'wpautop' );
    
    // Đảm bảo Gutenberg không cố gắng wpautop các block cổ điển
    remove_filter( 'render_block_core/freeform', 'wpautop' );
}
