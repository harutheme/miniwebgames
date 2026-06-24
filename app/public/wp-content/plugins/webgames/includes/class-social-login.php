<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Webgames_Social_Login {

    public function __construct() {
        add_action( 'init', array( $this, 'handle_oauth_callback' ) );
        add_action( 'init', array( $this, 'ensure_user_cookie' ) ); // Sync existing sessions
        add_action( 'comment_form_top', array( $this, 'render_social_buttons' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
        
        // Cache-friendly login state cookie
        add_action( 'set_auth_cookie', array( $this, 'set_user_cookie' ), 10, 5 );
        add_action( 'clear_auth_cookie', array( $this, 'clear_user_cookie' ) );
    }

    public function enqueue_styles() {
        if ( is_singular( 'game' ) ) {
            wp_enqueue_style( 'webgames-social-login', WEBGAMES_PLUGIN_URL . 'assets/css/social-login.css', array(), '1.0.0' );
        }
    }

    public function render_social_buttons() {
        if ( is_user_logged_in() ) {
            return;
        }

        $google_id = get_option( 'webgames_google_client_id' );
        $facebook_id = get_option( 'webgames_facebook_app_id' );

        if ( empty( $google_id ) && empty( $facebook_id ) ) {
            return;
        }

        // Must use admin-ajax or a clean endpoint for callback
        $callback_url = site_url( '?webgames_social_login=1' );
        $current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
        $state = base64_encode( json_encode( array(
            'redirect' => $current_url,
            'nonce'    => wp_create_nonce( 'wg_social_login_nonce' )
        ) ) );

        echo '<div class="wg-social-login-wrapper">';
        echo '<p class="wg-social-login-title">' . __( 'Login with social accounts to comment faster:', 'webgames' ) . '</p>';
        echo '<div class="wg-social-login-buttons">';

        if ( ! empty( $google_id ) ) {
            $google_auth_url = add_query_arg( array(
                'client_id'     => $google_id,
                'redirect_uri'  => $callback_url . '&provider=google',
                'response_type' => 'code',
                'scope'         => 'email profile',
                'state'         => $state,
            ), 'https://accounts.google.com/o/oauth2/v2/auth' );

            echo '<a href="' . esc_url( $google_auth_url ) . '" class="wg-btn-social wg-btn-google">';
            echo '<svg width="18" height="18" viewBox="0 0 48 48"><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/><path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/><path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/><path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/></svg>';
            echo '<span>' . __( 'Google', 'webgames' ) . '</span></a>';
        }

        if ( ! empty( $facebook_id ) ) {
            $fb_auth_url = add_query_arg( array(
                'client_id'     => $facebook_id,
                'redirect_uri'  => $callback_url . '&provider=facebook',
                'state'         => $state,
                'scope'         => 'email,public_profile',
            ), 'https://www.facebook.com/v19.0/dialog/oauth' );

            echo '<a href="' . esc_url( $fb_auth_url ) . '" class="wg-btn-social wg-btn-facebook">';
            echo '<svg width="18" height="18" viewBox="0 0 320 512"><path fill="#ffffff" d="M279.14 288l14.22-92.66h-88.91v-60.13c0-25.35 12.42-50.06 52.24-50.06h40.42V6.26S260.43 0 225.36 0c-73.22 0-121.08 44.38-121.08 124.72v70.62H22.89V288h81.39v224h100.17V288z"/></svg>';
            echo '<span>' . __( 'Facebook', 'webgames' ) . '</span></a>';
        }

        echo '</div></div>';
    }

    public function handle_oauth_callback() {
        if ( ! isset( $_GET['webgames_social_login'] ) || ! isset( $_GET['provider'] ) ) {
            return;
        }

        if ( isset( $_GET['error'] ) ) {
            // User likely cancelled
            $this->redirect_with_error( __( 'Login cancelled.', 'webgames' ) );
        }

        if ( ! isset( $_GET['code'] ) ) {
            return;
        }

        $provider = sanitize_text_field( $_GET['provider'] );
        $code = sanitize_text_field( $_GET['code'] );
        $state_raw = isset( $_GET['state'] ) ? $_GET['state'] : '';
        
        $state = json_decode( base64_decode( $state_raw ), true );
        $redirect_to = home_url();
        
        if ( is_array( $state ) && isset( $state['redirect'] ) && isset( $state['nonce'] ) ) {
            if ( wp_verify_nonce( $state['nonce'], 'wg_social_login_nonce' ) ) {
                $redirect_to = esc_url_raw( $state['redirect'] );
            }
        }

        $callback_url = site_url( '?webgames_social_login=1&provider=' . $provider );

        if ( $provider === 'google' ) {
            $this->process_google_login( $code, $callback_url, $redirect_to );
        } elseif ( $provider === 'facebook' ) {
            $this->process_facebook_login( $code, $callback_url, $redirect_to );
        }
    }

    private function redirect_with_error( $message, $redirect_to = '' ) {
        if ( empty( $redirect_to ) ) {
            $redirect_to = home_url();
        }
        // In a real app, we might want to store the error in a transient and show an alert
        wp_die( 
            '<h3>' . __( 'Login Failed', 'webgames' ) . '</h3>' .
            '<p>' . esc_html( $message ) . '</p>' .
            '<a href="' . esc_url( $redirect_to ) . '">' . __( 'Go Back', 'webgames' ) . '</a>'
        );
    }

    private function process_google_login( $code, $callback_url, $redirect_to ) {
        $client_id = get_option( 'webgames_google_client_id' );
        $client_secret = get_option( 'webgames_google_client_secret' );

        $response = wp_remote_post( 'https://oauth2.googleapis.com/token', array(
            'body' => array(
                'client_id'     => $client_id,
                'client_secret' => $client_secret,
                'redirect_uri'  => $callback_url,
                'grant_type'    => 'authorization_code',
                'code'          => $code,
            ),
        ) );

        if ( is_wp_error( $response ) ) {
            $this->redirect_with_error( 'Google API Error: ' . $response->get_error_message(), $redirect_to );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $body['access_token'] ) ) {
            $access_token = $body['access_token'];
            
            $user_info_response = wp_remote_get( 'https://www.googleapis.com/oauth2/v2/userinfo', array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $access_token,
                ),
            ) );

            if ( ! is_wp_error( $user_info_response ) ) {
                $user_info = json_decode( wp_remote_retrieve_body( $user_info_response ), true );
                if ( isset( $user_info['email'] ) ) {
                    $this->login_or_register_user( $user_info['email'], isset( $user_info['name'] ) ? $user_info['name'] : '', $redirect_to );
                } else {
                    $this->redirect_with_error( __( 'Error: Google did not return an email address.', 'webgames' ), $redirect_to );
                }
            } else {
                $this->redirect_with_error( 'Google Profile Error.', $redirect_to );
            }
        } else {
            $this->redirect_with_error( 'Google Token Error. Please try again.', $redirect_to );
        }
    }

    private function process_facebook_login( $code, $callback_url, $redirect_to ) {
        $app_id = get_option( 'webgames_facebook_app_id' );
        $app_secret = get_option( 'webgames_facebook_app_secret' );

        $token_url = add_query_arg( array(
            'client_id'     => $app_id,
            'client_secret' => $app_secret,
            'redirect_uri'  => $callback_url,
            'code'          => $code,
        ), 'https://graph.facebook.com/v19.0/oauth/access_token' );

        $response = wp_remote_get( $token_url );

        if ( is_wp_error( $response ) ) {
            $this->redirect_with_error( 'Facebook API Error: ' . $response->get_error_message(), $redirect_to );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( isset( $body['access_token'] ) ) {
            $access_token = $body['access_token'];
            
            $user_info_response = wp_remote_get( 'https://graph.facebook.com/me?fields=id,name,email&access_token=' . $access_token );

            if ( ! is_wp_error( $user_info_response ) ) {
                $user_info = json_decode( wp_remote_retrieve_body( $user_info_response ), true );
                if ( isset( $user_info['email'] ) && ! empty( $user_info['email'] ) ) {
                    $this->login_or_register_user( $user_info['email'], isset( $user_info['name'] ) ? $user_info['name'] : '', $redirect_to );
                } else {
                    // Case 3: Facebook does not return email
                    $this->redirect_with_error( __( 'Your Facebook account does not have a linked email address, which is required to comment. Please use Google Login or update your Facebook account.', 'webgames' ), $redirect_to );
                }
            } else {
                $this->redirect_with_error( 'Facebook Profile Error.', $redirect_to );
            }
        } else {
            $error_msg = isset( $body['error']['message'] ) ? $body['error']['message'] : 'Unknown error';
            $this->redirect_with_error( 'Facebook Token Error: ' . $error_msg, $redirect_to );
        }
    }

    private function login_or_register_user( $email, $name, $redirect_to ) {
        $user = get_user_by( 'email', $email );
        
        if ( $user ) {
            // Case 2: User exists, merge and login
            wp_set_current_user( $user->ID );
            wp_set_auth_cookie( $user->ID );
        } else {
            // Case 1: Create new user
            $username = sanitize_user( current( explode( '@', $email ) ), true );
            
            // Ensure unique username
            $original_username = $username;
            $i = 1;
            while ( username_exists( $username ) ) {
                $username = $original_username . $i;
                $i++;
            }

            $password = wp_generate_password( 16, false );
            $user_id = wp_create_user( $username, $password, $email );

            if ( ! is_wp_error( $user_id ) ) {
                $name_parts = explode( ' ', $name );
                $first_name = $name_parts[0];
                $last_name = isset( $name_parts[1] ) ? $name_parts[1] : '';

                wp_update_user( array(
                    'ID'           => $user_id,
                    'first_name'   => $first_name,
                    'last_name'    => $last_name,
                    'display_name' => $name,
                ) );

                wp_set_current_user( $user_id );
                wp_set_auth_cookie( $user_id );
            } else {
                $this->redirect_with_error( 'Error creating user account: ' . $user_id->get_error_message(), $redirect_to );
            }
        }

        wp_safe_redirect( $redirect_to );
        exit;
    }

    public function set_user_cookie( $auth_cookie, $expire, $expiration, $user_id, $scheme ) {
        $user = get_userdata( $user_id );
        if ( $user ) {
            $name = ! empty( $user->first_name ) ? $user->first_name : $user->display_name;
            $data = array(
                'name' => $name,
                'avatar' => get_avatar_url( $user_id, array( 'size' => 32 ) )
            );
            // Must NOT be HttpOnly so JS can read it
            setcookie( 'wg_user_info', base64_encode( json_encode( $data ) ), $expire, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), false );
        }
    }

    public function clear_user_cookie() {
        setcookie( 'wg_user_info', ' ', time() - YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );
    }

    public function ensure_user_cookie() {
        // Run on init. If logged in but no JS cookie, set it!
        if ( is_user_logged_in() && ! isset( $_COOKIE['wg_user_info'] ) && ! headers_sent() ) {
            $user = wp_get_current_user();
            $name = ! empty( $user->first_name ) ? $user->first_name : $user->display_name;
            $data = array(
                'name' => $name,
                'avatar' => get_avatar_url( $user->ID, array( 'size' => 32 ) )
            );
            setcookie( 'wg_user_info', base64_encode( json_encode( $data ) ), time() + 14 * DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), false );
        }
    }
}
