<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Webgames_CPT_Game {

    public function __construct() {
        add_action( 'init', array( $this, 'register_cpt' ) );
        add_action( 'init', array( $this, 'register_taxonomies' ) );

        // Taxonomy Meta Hooks
        add_action( 'game-category_edit_form_fields', array( $this, 'edit_taxonomy_seo_field' ), 10, 2 );
        add_action( 'edited_game-category', array( $this, 'save_taxonomy_seo_field' ), 10, 2 );
        
        add_action( 'game-tag_edit_form_fields', array( $this, 'edit_taxonomy_seo_field' ), 10, 2 );
        add_action( 'edited_game-tag', array( $this, 'save_taxonomy_seo_field' ), 10, 2 );
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
}
