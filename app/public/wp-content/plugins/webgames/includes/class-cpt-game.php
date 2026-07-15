<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Webgames_CPT_Game {

    public function __construct() {
        add_action( 'init', array( $this, 'register_cpt' ) );
        add_action( 'init', array( $this, 'register_taxonomies' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

        // Taxonomy Meta Hooks
        add_action( 'game-category_add_form_fields', array( $this, 'add_taxonomy_custom_fields' ), 10, 1 );
        add_action( 'game-category_edit_form_fields', array( $this, 'edit_taxonomy_custom_fields' ), 10, 2 );
        add_action( 'created_game-category', array( $this, 'save_taxonomy_custom_fields' ), 10, 2 );
        add_action( 'edited_game-category', array( $this, 'save_taxonomy_custom_fields' ), 10, 2 );

        add_action( 'game-tag_add_form_fields', array( $this, 'add_taxonomy_custom_fields' ), 10, 1 );
        add_action( 'game-tag_edit_form_fields', array( $this, 'edit_taxonomy_custom_fields' ), 10, 2 );
        add_action( 'created_game-tag', array( $this, 'save_taxonomy_custom_fields' ), 10, 2 );
        add_action( 'edited_game-tag', array( $this, 'save_taxonomy_custom_fields' ), 10, 2 );

        add_action( 'game-category_edit_form_fields', array( $this, 'edit_taxonomy_seo_field' ), 10, 2 );
        add_action( 'edited_game-category', array( $this, 'save_taxonomy_seo_field' ), 10, 2 );
        
        add_action( 'game-tag_edit_form_fields', array( $this, 'edit_taxonomy_seo_field' ), 10, 2 );
        add_action( 'edited_game-tag', array( $this, 'save_taxonomy_seo_field' ), 10, 2 );

        // Rank Math SEO filters
        add_filter( 'rank_math/opengraph/facebook/image', array( $this, 'rank_math_term_image' ) );
        add_filter( 'rank_math/opengraph/twitter/image', array( $this, 'rank_math_term_image' ) );
    }

    public function enqueue_admin_scripts( $hook ) {
        if ( $hook === 'edit-tags.php' || $hook === 'term.php' ) {
            wp_enqueue_media();
        }
    }

    public function register_cpt() {
        $labels = array(
            'name'                  => _x( 'Games', 'Post Type General Name', 'webgames' ),
            'singular_name'         => _x( 'Game', 'Post Type Singular Name', 'webgames' ),
            'menu_name'             => __( 'Games', 'webgames' ),
            'name_admin_bar'        => __( 'Game', 'webgames' ),
            'archives'              => __( 'Game Archives', 'webgames' ),
            'attributes'            => __( 'Game Attributes', 'webgames' ),
            'parent_item_colon'     => __( 'Parent Game:', 'webgames' ),
            'all_items'             => __( 'All Games', 'webgames' ),
            'add_new_item'          => __( 'Add New Game', 'webgames' ),
            'add_new'               => __( 'Add New', 'webgames' ),
            'new_item'              => __( 'New Game', 'webgames' ),
            'edit_item'             => __( 'Edit Game', 'webgames' ),
            'update_item'           => __( 'Update Game', 'webgames' ),
            'view_item'             => __( 'View Game', 'webgames' ),
            'view_items'            => __( 'View Games', 'webgames' ),
            'search_items'          => __( 'Search Game', 'webgames' ),
            'not_found'             => __( 'Not found', 'webgames' ),
            'not_found_in_trash'    => __( 'Not found in Trash', 'webgames' ),
            'featured_image'        => __( 'Featured Image', 'webgames' ),
            'set_featured_image'    => __( 'Set featured image', 'webgames' ),
            'remove_featured_image' => __( 'Remove featured image', 'webgames' ),
            'use_featured_image'    => __( 'Use as featured image', 'webgames' ),
            'insert_into_item'      => __( 'Insert into game', 'webgames' ),
            'uploaded_to_this_item' => __( 'Uploaded to this game', 'webgames' ),
            'items_list'            => __( 'Games list', 'webgames' ),
            'items_list_navigation' => __( 'Games list navigation', 'webgames' ),
            'filter_items_list'     => __( 'Filter games list', 'webgames' ),
        );
        $args = array(
            'label'                 => __( 'Game', 'webgames' ),
            'description'           => __( 'Web games post type', 'webgames' ),
            'labels'                => $labels,
            'supports'              => array( 'title', 'editor', 'thumbnail', 'excerpt', 'comments', 'custom-fields' ),
            'taxonomies'            => array( 'game-category', 'game-tag' ),
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
            'menu_position'         => 5,
            'menu_icon'             => 'dashicons-games',
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => true,
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'post',
            'show_in_rest'          => true, // Enable Gutenberg block editor
        );
        register_post_type( 'game', $args );
    }

    public function register_taxonomies() {
        // Game Category
        $cat_labels = array(
            'name'                       => _x( 'Game Categories', 'Taxonomy General Name', 'webgames' ),
            'singular_name'              => _x( 'Game Category', 'Taxonomy Singular Name', 'webgames' ),
            'menu_name'                  => __( 'Categories', 'webgames' ),
            'all_items'                  => __( 'All Categories', 'webgames' ),
            'parent_item'                => __( 'Parent Category', 'webgames' ),
            'parent_item_colon'          => __( 'Parent Category:', 'webgames' ),
            'new_item_name'              => __( 'New Category Name', 'webgames' ),
            'add_new_item'               => __( 'Add New Category', 'webgames' ),
            'edit_item'                  => __( 'Edit Category', 'webgames' ),
            'update_item'                => __( 'Update Category', 'webgames' ),
            'view_item'                  => __( 'View Category', 'webgames' ),
            'separate_items_with_commas' => __( 'Separate categories with commas', 'webgames' ),
            'add_or_remove_items'        => __( 'Add or remove categories', 'webgames' ),
            'choose_from_most_used'      => __( 'Choose from the most used', 'webgames' ),
            'popular_items'              => __( 'Popular Categories', 'webgames' ),
            'search_items'               => __( 'Search Categories', 'webgames' ),
            'not_found'                  => __( 'Not Found', 'webgames' ),
            'no_terms'                   => __( 'No categories', 'webgames' ),
            'items_list'                 => __( 'Categories list', 'webgames' ),
            'items_list_navigation'      => __( 'Categories list navigation', 'webgames' ),
        );
        $cat_args = array(
            'labels'                     => $cat_labels,
            'hierarchical'               => true,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => true,
            'show_tagcloud'              => true,
            'show_in_rest'               => true,
        );
        register_taxonomy( 'game-category', array( 'game' ), $cat_args );

        // Game Tag
        $tag_labels = array(
            'name'                       => _x( 'Game Tags', 'Taxonomy General Name', 'webgames' ),
            'singular_name'              => _x( 'Game Tag', 'Taxonomy Singular Name', 'webgames' ),
            'menu_name'                  => __( 'Tags', 'webgames' ),
            'all_items'                  => __( 'All Tags', 'webgames' ),
            'parent_item'                => __( 'Parent Tag', 'webgames' ),
            'parent_item_colon'          => __( 'Parent Tag:', 'webgames' ),
            'new_item_name'              => __( 'New Tag Name', 'webgames' ),
            'add_new_item'               => __( 'Add New Tag', 'webgames' ),
            'edit_item'                  => __( 'Edit Tag', 'webgames' ),
            'update_item'                => __( 'Update Tag', 'webgames' ),
            'view_item'                  => __( 'View Tag', 'webgames' ),
            'separate_items_with_commas' => __( 'Separate tags with commas', 'webgames' ),
            'add_or_remove_items'        => __( 'Add or remove tags', 'webgames' ),
            'choose_from_most_used'      => __( 'Choose from the most used', 'webgames' ),
            'popular_items'              => __( 'Popular Tags', 'webgames' ),
            'search_items'               => __( 'Search Tags', 'webgames' ),
            'not_found'                  => __( 'Not Found', 'webgames' ),
            'no_terms'                   => __( 'No tags', 'webgames' ),
            'items_list'                 => __( 'Tags list', 'webgames' ),
            'items_list_navigation'      => __( 'Tags list navigation', 'webgames' ),
        );
        $tag_args = array(
            'labels'                     => $tag_labels,
            'hierarchical'               => false,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => true,
            'show_tagcloud'              => true,
            'show_in_rest'               => true,
        );
        register_taxonomy( 'game-tag', array( 'game' ), $tag_args );
    }

    public function add_taxonomy_custom_fields( $taxonomy ) {
        ?>
        <div class="form-field term-icon-class-wrap">
            <label for="wg_category_icon_class"><?php _e( 'Icon Class', 'webgames' ); ?></label>
            <input type="text" name="wg_category_icon_class" id="wg_category_icon_class" value="" />
            <p class="description"><?php _e( 'Enter a Dashicon class (e.g., dashicons-car, dashicons-games). This is used if no image is uploaded.', 'webgames' ); ?></p>
        </div>
        <div class="form-field term-image-wrap">
            <label for="wg_category_image"><?php _e( 'Term Image', 'webgames' ); ?></label>
            <input type="hidden" name="wg_category_image" id="wg_category_image" value="" />
            <div id="wg_category_image_preview" style="margin-top: 10px; margin-bottom: 10px;"></div>
            <button class="button" id="wg_category_image_button"><?php _e( 'Upload/Select Image', 'webgames' ); ?></button>
            <button class="button" id="wg_category_image_remove" style="display:none;"><?php _e( 'Remove Image', 'webgames' ); ?></button>
            <?php $this->render_media_uploader_js(); ?>
        </div>
        <?php
    }

    public function edit_taxonomy_custom_fields( $term, $taxonomy ) {
        $icon_class = get_term_meta( $term->term_id, 'wg_category_icon_class', true );
        $image_id = get_term_meta( $term->term_id, 'wg_category_image', true );
        $image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'thumbnail' ) : '';
        ?>
        <tr class="form-field term-icon-class-wrap">
            <th scope="row"><label for="wg_category_icon_class"><?php _e( 'Icon Class', 'webgames' ); ?></label></th>
            <td>
                <input type="text" name="wg_category_icon_class" id="wg_category_icon_class" value="<?php echo esc_attr( $icon_class ); ?>" />
                <p class="description"><?php _e( 'Enter a Dashicon class (e.g., dashicons-car, dashicons-games). This is used if no image is uploaded.', 'webgames' ); ?></p>
            </td>
        </tr>
        <tr class="form-field term-image-wrap">
            <th scope="row"><label for="wg_category_image"><?php _e( 'Term Image', 'webgames' ); ?></label></th>
            <td>
                <input type="hidden" name="wg_category_image" id="wg_category_image" value="<?php echo esc_attr( $image_id ); ?>" />
                <div id="wg_category_image_preview" style="margin-top: 10px; margin-bottom: 10px;">
                    <?php if ( $image_url ) echo '<img src="' . esc_url( $image_url ) . '" style="max-width:100px; height:auto;" />'; ?>
                </div>
                <button class="button" id="wg_category_image_button"><?php _e( 'Upload/Select Image', 'webgames' ); ?></button>
                <button class="button" id="wg_category_image_remove" style="<?php echo $image_id ? '' : 'display:none;'; ?>"><?php _e( 'Remove Image', 'webgames' ); ?></button>
                <?php $this->render_media_uploader_js(); ?>
                <p class="description"><?php _e( 'If an image is uploaded, it will be prioritized over the Icon Class.', 'webgames' ); ?></p>
            </td>
        </tr>
        <?php
    }

    private function render_media_uploader_js() {
        ?>
        <script>
        jQuery(document).ready(function($){
            var mediaUploader;
            $('#wg_category_image_button').click(function(e) {
                e.preventDefault();
                if (mediaUploader) { mediaUploader.open(); return; }
                mediaUploader = wp.media.frames.file_frame = wp.media({ title: 'Choose Image', button: { text: 'Choose Image' }, multiple: false });
                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#wg_category_image').val(attachment.id);
                    $('#wg_category_image_preview').html('<img src="'+attachment.url+'" style="max-width:100px; height:auto;"/>');
                    $('#wg_category_image_remove').show();
                });
                mediaUploader.open();
            });
            $('#wg_category_image_remove').click(function(e){
                e.preventDefault();
                $('#wg_category_image').val('');
                $('#wg_category_image_preview').html('');
                $(this).hide();
            });
        });
        </script>
        <?php
    }

    public function save_taxonomy_custom_fields( $term_id, $tt_id ) {
        if ( isset( $_POST['wg_category_icon_class'] ) ) {
            update_term_meta( $term_id, 'wg_category_icon_class', sanitize_text_field( $_POST['wg_category_icon_class'] ) );
        }
        if ( isset( $_POST['wg_category_image'] ) ) {
            update_term_meta( $term_id, 'wg_category_image', sanitize_text_field( $_POST['wg_category_image'] ) );
        }
    }

    public function edit_taxonomy_seo_field( $term, $taxonomy ) {
        $seo_content = get_term_meta( $term->term_id, 'bottom_seo_content', true );
        ?>
        <tr class="form-field term-seo-content-wrap">
            <th scope="row"><label for="bottom_seo_content"><?php _e( 'Bottom SEO Content', 'webgames' ); ?></label></th>
            <td>
                <?php 
                wp_editor( $seo_content, 'bottom_seo_content', array(
                    'textarea_name' => 'bottom_seo_content',
                    'textarea_rows' => 10,
                    'media_buttons' => true,
                ) ); 
                ?>
                <p class="description"><?php _e( 'This content will appear at the very bottom of the archive page for SEO purposes.', 'webgames' ); ?></p>
            </td>
        </tr>
        <?php
    }

    public function save_taxonomy_seo_field( $term_id, $tt_id ) {
        if ( isset( $_POST['bottom_seo_content'] ) ) {
            // Using wp_kses_post to allow safe HTML
            update_term_meta( $term_id, 'bottom_seo_content', wp_kses_post( $_POST['bottom_seo_content'] ) );
        }
    }

    public function rank_math_term_image( $attachment_url ) {
        if ( is_tax( 'game-category' ) || is_tax( 'game-tag' ) ) {
            $term = get_queried_object();
            if ( $term ) {
                $image_id = get_term_meta( $term->term_id, 'wg_category_image', true );
                if ( $image_id ) {
                    $image_url = wp_get_attachment_image_url( $image_id, 'full' );
                    if ( $image_url ) {
                        return $image_url;
                    }
                }
            }
        }
        return $attachment_url;
    }
}
