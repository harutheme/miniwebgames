<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Webgames_CPT_Report {

    public function __construct() {
        add_action( 'init', array( $this, 'register_cpt' ) );
    }

    public function register_cpt() {
        $labels = array(
            'name'                  => _x( 'Game Reports', 'Post Type General Name', 'webgames' ),
            'singular_name'         => _x( 'Game Report', 'Post Type Singular Name', 'webgames' ),
            'menu_name'             => __( 'Game Reports', 'webgames' ),
            'name_admin_bar'        => __( 'Game Report', 'webgames' ),
            'all_items'             => __( 'All Reports', 'webgames' ),
            'view_item'             => __( 'View Report', 'webgames' ),
            'search_items'          => __( 'Search Report', 'webgames' ),
            'not_found'             => __( 'Not found', 'webgames' ),
            'not_found_in_trash'    => __( 'Not found in Trash', 'webgames' ),
        );
        $args = array(
            'label'                 => __( 'Game Report', 'webgames' ),
            'labels'                => $labels,
            'supports'              => array( 'title', 'editor', 'custom-fields' ),
            'hierarchical'          => false,
            'public'                => false, // Hidden from frontend
            'show_ui'               => true,  // Show in admin
            'show_in_menu'          => 'edit.php?post_type=game', // Nested under Games menu
            'menu_position'         => 10,
            'show_in_admin_bar'     => false,
            'show_in_nav_menus'     => false,
            'can_export'            => true,
            'has_archive'           => false,
            'exclude_from_search'   => true,
            'publicly_queryable'    => false,
            'capability_type'       => 'post',
            'capabilities' => array(
                'create_posts' => 'do_not_allow', // Prevents admin from manually creating
            ),
            'map_meta_cap' => true,
        );
        register_post_type( 'game-report', $args );
    }
}
