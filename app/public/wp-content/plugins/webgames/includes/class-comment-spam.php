<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Webgames_Comment_Spam {

    public function __construct() {
        // Output honeypot field in comment form
        add_action( 'comment_form_after_fields', array( $this, 'add_honeypot_field' ) );
        add_action( 'comment_form_logged_in_after', array( $this, 'add_honeypot_field' ) );

        // Validate honeypot field before processing comment
        add_filter( 'preprocess_comment', array( $this, 'validate_honeypot' ) );
    }

    public function add_honeypot_field() {
        // We use a realistic name 'website_url_field' to trick bots.
        // The inline CSS hides it from real users.
        echo '<p style="display:none !important; visibility:hidden !important;" class="webgames-hp-container">';
        echo '<label for="website_url_field">Leave this field empty if you are human:</label>';
        echo '<input type="text" name="website_url_field" id="website_url_field" value="" tabindex="-1" autocomplete="off" />';
        echo '</p>';
    }

    public function validate_honeypot( $commentdata ) {
        // If the honeypot field is filled, it's a bot
        if ( isset( $_POST['website_url_field'] ) && ! empty( trim( $_POST['website_url_field'] ) ) ) {
            // Silently die or return an error
            wp_die( __( 'Spam detected. Comment rejected.', 'webgames' ), 403 );
        }
        return $commentdata;
    }
}
