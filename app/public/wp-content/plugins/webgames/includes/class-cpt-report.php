<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Webgames_CPT_Report {

    public function __construct() {
        add_action( 'init', array( $this, 'register_cpt' ) );
        add_filter( 'manage_game-report_posts_columns', array( $this, 'custom_columns' ) );
        add_action( 'manage_game-report_posts_custom_column', array( $this, 'custom_column_data' ), 10, 2 );
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

    public function custom_columns( $columns ) {
        $new_columns = array();
        if ( isset( $columns['cb'] ) ) {
            $new_columns['cb'] = $columns['cb'];
        }
        $new_columns['title'] = __( 'Title', 'webgames' );
        $new_columns['report_content'] = __( 'Content', 'webgames' );
        $new_columns['reported_game'] = __( 'Edit Game', 'webgames' );
        $new_columns['reporter_info'] = __( 'Reporter', 'webgames' );
        $new_columns['date'] = __( 'Date', 'webgames' );
        
        return $new_columns;
    }

    public function custom_column_data( $column, $post_id ) {
        switch ( $column ) {
            case 'report_content':
                $content = get_post_field( 'post_content', $post_id );
                if ( mb_strlen( $content ) > 100 ) {
                    echo esc_html( mb_substr( $content, 0, 100 ) . '...' );
                } else {
                    echo esc_html( $content );
                }
                break;
                
            case 'reported_game':
                $game_id = get_post_meta( $post_id, 'reported_game_id', true );
                if ( $game_id ) {
                    $edit_link = get_edit_post_link( $game_id );
                    $game_title = get_the_title( $game_id );
                    if ( $edit_link ) {
                        echo '<a href="' . esc_url( $edit_link ) . '">' . esc_html( $game_title ) . '</a>';
                    } else {
                        echo esc_html( $game_title );
                    }
                } else {
                    echo 'N/A';
                }
                break;
                
            case 'reporter_info':
                $ip = get_post_meta( $post_id, 'reporter_ip', true );
                $user_id = get_post_meta( $post_id, 'reporter_user_id', true );
                
                if ( $user_id ) {
                    $user_info = get_userdata( $user_id );
                    if ( $user_info ) {
                        $edit_user_link = get_edit_user_link( $user_id );
                        echo '<strong>User:</strong> <a href="' . esc_url( $edit_user_link ) . '">' . esc_html( $user_info->user_login ) . '</a><br>';
                    } else {
                        echo '<strong>User:</strong> ID ' . esc_html( $user_id ) . '<br>';
                    }
                } else {
                    echo '<strong>User:</strong> Guest<br>';
                }
                
                if ( $ip ) {
                    echo '<strong>IP:</strong> ' . esc_html( $ip );
                }
                break;
        }
    }
}
