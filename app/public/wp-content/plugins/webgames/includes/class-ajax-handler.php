<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Webgames_Ajax_Handler {

    public function __construct() {
        // AJAX actions for logged-in and guest users
        add_action( 'wp_ajax_webgames_like', array( $this, 'handle_like' ) );
        add_action( 'wp_ajax_nopriv_webgames_like', array( $this, 'handle_like' ) );

        add_action( 'wp_ajax_webgames_report', array( $this, 'handle_report' ) );
        add_action( 'wp_ajax_nopriv_webgames_report', array( $this, 'handle_report' ) );

        add_action( 'wp_ajax_webgames_track_view', array( $this, 'handle_track_view' ) );
        add_action( 'wp_ajax_nopriv_webgames_track_view', array( $this, 'handle_track_view' ) );
    }

    public function handle_like() {
        // CSRF protection is only necessary for authenticated sessions.
        // For guests, we rely on IP transients to prevent spam, avoiding page-cache nonce issues.
        if ( is_user_logged_in() ) {
            if ( ! check_ajax_referer( 'webgames_nonce', 'security', false ) ) {
                wp_send_json_error( 'Security check failed. Please refresh the page and try again.' );
                wp_die();
            }
        }

        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
        $action_type = isset( $_POST['action_type'] ) ? sanitize_text_field( $_POST['action_type'] ) : 'like';

        if ( ! $post_id || ! in_array( $action_type, array( 'like', 'dislike' ) ) ) {
            wp_send_json_error( 'Invalid request' );
        }

        // Anti-spam Check (1 vote per IP per day)
        $ip = $_SERVER['REMOTE_ADDR'];
        $transient_key = 'wg_vote_' . $post_id . '_' . md5( $ip );
        
        $previous_vote = get_transient( $transient_key );

        // Get current real counts
        $real_like = (int) get_post_meta( $post_id, '_real_like', true );
        $real_dislike = (int) get_post_meta( $post_id, '_real_dislike', true );

        $is_unvote = false;

        if ( $previous_vote && $previous_vote === $action_type ) {
            // Unvote action
            $is_unvote = true;
            if ( $action_type === 'like' ) {
                $real_like = max( 0, $real_like - 1 );
                update_post_meta( $post_id, '_real_like', $real_like );
            } else {
                $real_dislike = max( 0, $real_dislike - 1 );
                update_post_meta( $post_id, '_real_dislike', $real_dislike );
            }
            delete_transient( $transient_key );
        } else {
            // Switching or New Vote
            if ( $previous_vote ) {
                if ( $previous_vote === 'like' ) {
                    $real_like = max( 0, $real_like - 1 );
                    update_post_meta( $post_id, '_real_like', $real_like );
                } elseif ( $previous_vote === 'dislike' ) {
                    $real_dislike = max( 0, $real_dislike - 1 );
                    update_post_meta( $post_id, '_real_dislike', $real_dislike );
                }
            }

            // Increment selected
            if ( $action_type === 'like' ) {
                $real_like++;
                update_post_meta( $post_id, '_real_like', $real_like );
            } else {
                $real_dislike++;
                update_post_meta( $post_id, '_real_dislike', $real_dislike );
            }
            set_transient( $transient_key, $action_type, DAY_IN_SECONDS );
        }

        // Get fake counts (fallback to defaults if empty)
        $fake_like = get_post_meta( $post_id, 'fake_like', true );
        $fake_like = ( $fake_like !== '' ) ? (int) $fake_like : 100;

        $fake_dislike = get_post_meta( $post_id, 'fake_dislike', true );
        $fake_dislike = ( $fake_dislike !== '' ) ? (int) $fake_dislike : 10;

        // Calculate Totals
        $total_like = $fake_like + $real_like;
        $total_dislike = $fake_dislike + $real_dislike;
        $total_votes = $total_like + $total_dislike;
        
        $rating = 0;
        if ( $total_votes > 0 ) {
            $rating = round( ( $total_like / $total_votes ) * 100 );
        }

        // Save Rating
        update_post_meta( $post_id, '_game_rating', $rating );

        // Set transient to prevent multiple votes (24 hours)
        set_transient( $transient_key, $action_type, DAY_IN_SECONDS );

        wp_send_json_success( array( 
            'total_like' => $total_like,
            'total_dislike' => $total_dislike,
            'total_like_formatted' => webgames_format_number( $total_like ),
            'total_dislike_formatted' => webgames_format_number( $total_dislike ),
            'rating' => $rating,
            'action_result' => $is_unvote ? 'unvoted' : $action_type
        ) );
    }

    public function handle_report() {
        if ( is_user_logged_in() ) {
            if ( ! check_ajax_referer( 'webgames_nonce', 'security', false ) ) {
                wp_send_json_error( 'Security check failed. Please refresh the page and try again.' );
                wp_die();
            }
        }

        // Check Honeypot
        if ( ! empty( $_POST['hp_field'] ) ) {
            wp_send_json_error( 'Bot detected' );
            wp_die();
        }

        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
        $reason = isset( $_POST['reason'] ) ? sanitize_textarea_field( $_POST['reason'] ) : '';

        if ( ! $post_id || empty( $reason ) ) {
            wp_send_json_error( 'Missing data' );
            wp_die();
        }

        $reason_len = mb_strlen( trim( $reason ) );
        if ( $reason_len < 10 ) {
            wp_send_json_error( __( 'Reason is too short. Please provide more details.', 'webgames' ) );
            wp_die();
        }
        if ( $reason_len > 500 ) {
            wp_send_json_error( __( 'Reason is too long. Please keep it under 500 characters.', 'webgames' ) );
            wp_die();
        }

        // Rate limiting by IP
        $ip = $_SERVER['REMOTE_ADDR'];
        $transient_key = 'wg_report_' . $post_id . '_' . md5( $ip );
        
        if ( get_transient( $transient_key ) ) {
            wp_send_json_error( __( 'You have already reported this game recently.', 'webgames' ) );
            wp_die();
        }

        $meta_input = array(
            'reported_game_id' => $post_id,
            'reporter_ip'      => $ip,
        );

        if ( is_user_logged_in() ) {
            $meta_input['reporter_user_id'] = get_current_user_id();
        }

        // Create Report Post
        $game_title = get_the_title( $post_id );
        $report_id = wp_insert_post( array(
            'post_type'    => 'game-report',
            'post_title'   => sprintf( __( 'Report: %s', 'webgames' ), $game_title ),
            'post_content' => $reason,
            'post_status'  => 'publish',
            'meta_input'   => $meta_input,
        ) );

        if ( $report_id && ! is_wp_error( $report_id ) ) {
            // Set transient for 24 hours
            set_transient( $transient_key, true, DAY_IN_SECONDS );

            // Send Email to Admin
            $admin_email = get_option( 'admin_email' );
            $subject = sprintf( __( 'New Game Report: %s', 'webgames' ), $game_title );
            $message = sprintf( __( "A new report has been submitted.\n\nGame: %s\nReason: %s\nIP: %s\nReport ID: %d", 'webgames' ), $game_title, $reason, $ip, $report_id );
            wp_mail( $admin_email, $subject, $message );

            wp_send_json_success( __( 'Report submitted successfully. Thank you.', 'webgames' ) );
        } else {
            wp_send_json_error( __( 'Failed to submit report.', 'webgames' ) );
        }
    }

    public function handle_track_view() {
        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
        
        if ( ! $post_id ) {
            wp_send_json_error( 'Missing data' );
            wp_die();
        }

        // Rate limit views by IP to prevent spamming
        $ip = $_SERVER['REMOTE_ADDR'];
        $transient_key = 'wg_view_' . $post_id . '_' . md5( $ip );
        
        if ( ! get_transient( $transient_key ) ) {
            $views = (int) get_post_meta( $post_id, 'wg_views', true );
            $views++;
            update_post_meta( $post_id, 'wg_views', $views );
            
            // Set a 30-minute block for the same IP viewing the same game
            set_transient( $transient_key, true, 30 * MINUTE_IN_SECONDS );
            
            wp_send_json_success( array( 'views' => $views ) );
        } else {
            wp_send_json_success( array( 'message' => 'View already counted' ) );
        }
        
        wp_die();
    }
}
