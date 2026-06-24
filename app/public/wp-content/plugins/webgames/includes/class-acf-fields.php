<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Webgames_ACF_Fields {

    public function __construct() {
        add_action( 'acf/init', array( $this, 'register_fields' ) );
    }

    public function register_fields() {
        if ( function_exists( 'acf_add_local_field_group' ) ) {
            acf_add_local_field_group( array(
                'key' => 'group_webgames_game_data',
                'title' => __( 'Game Data', 'webgames' ),
                'fields' => array(
                    array(
                        'key' => 'field_webgames_source_type',
                        'label' => __( 'Game Source Type', 'webgames' ),
                        'name' => 'source_type',
                        'type' => 'radio',
                        'instructions' => __( 'Choose how this game is loaded.', 'webgames' ),
                        'required' => 1,
                        'choices' => array(
                            'html5' => __( 'HTML5 URL', 'webgames' ),
                            'iframe' => __( 'External Iframe', 'webgames' ),
                        ),
                        'default_value' => 'html5',
                        'layout' => 'horizontal',
                    ),
                    array(
                        'key' => 'field_webgames_html5_url',
                        'label' => __( 'HTML5 URL', 'webgames' ),
                        'name' => 'html5_url',
                        'type' => 'url',
                        'instructions' => __( 'Enter the full URL to the HTML5 game (e.g. index.html).', 'webgames' ),
                        'required' => 1,
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field' => 'field_webgames_source_type',
                                    'operator' => '==',
                                    'value' => 'html5',
                                ),
                            ),
                        ),
                    ),
                    array(
                        'key' => 'field_webgames_iframe_url',
                        'label' => __( 'Iframe URL', 'webgames' ),
                        'name' => 'iframe_url',
                        'type' => 'url',
                        'instructions' => __( 'Enter the external URL to embed.', 'webgames' ),
                        'required' => 1,
                        'conditional_logic' => array(
                            array(
                                array(
                                    'field' => 'field_webgames_source_type',
                                    'operator' => '==',
                                    'value' => 'iframe',
                                ),
                            ),
                        ),
                    ),
                    array(
                        'key' => 'field_webgames_game_label',
                        'label' => __( 'Game Label', 'webgames' ),
                        'name' => 'game_label',
                        'type' => 'radio',
                        'instructions' => __( 'Highlight this game with a label.', 'webgames' ),
                        'choices' => array(
                            'none' => __( 'None', 'webgames' ),
                            'hot' => __( 'HOT', 'webgames' ),
                            'new' => __( 'NEW', 'webgames' ),
                        ),
                        'default_value' => 'none',
                        'layout' => 'horizontal',
                    ),
                    array(
                        'key' => 'field_webgames_game_cover',
                        'label' => __( 'Game Cover Image', 'webgames' ),
                        'name' => 'game_cover',
                        'type' => 'image',
                        'instructions' => __( 'Used as the thumbnail before clicking play.', 'webgames' ),
                        'return_format' => 'url',
                        'preview_size' => 'medium',
                    ),
                    array(
                        'key' => 'field_webgames_fake_like',
                        'label' => __( 'Fake Like', 'webgames' ),
                        'name' => 'fake_like',
                        'type' => 'number',
                        'instructions' => __( 'Default is 100. Base number of likes.', 'webgames' ),
                        'default_value' => 100,
                        'min' => 0,
                    ),
                    array(
                        'key' => 'field_webgames_fake_dislike',
                        'label' => __( 'Fake Dislike', 'webgames' ),
                        'name' => 'fake_dislike',
                        'type' => 'number',
                        'instructions' => __( 'Default is 10. Base number of dislikes.', 'webgames' ),
                        'default_value' => 10,
                        'min' => 0,
                    ),
                ),
                'location' => array(
                    array(
                        array(
                            'param' => 'post_type',
                            'operator' => '==',
                            'value' => 'game',
                        ),
                    ),
                ),
                'menu_order' => 0,
                'position' => 'normal',
                'style' => 'default',
                'label_placement' => 'top',
                'instruction_placement' => 'label',
                'hide_on_screen' => '',
                'active' => true,
                'description' => '',
            ) );
        }
    }
}
