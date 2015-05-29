<?php

//TODO: Make this class independent from the rest of the code (make module from it and move it to the module folder)
class Contributer_Facebook_Login {
    
    private $client_id;
    
    private $client_secret;
    
    private $response_messages = array();
    
    
    
    public function __construct() {
        $this->client_id = Sensei_Options::get_instance()->get_option( 'facebook_app_id' );
        $this->client_secret = Sensei_Options::get_instance()->get_option( 'facebook_app_secret' );
        $this->populate_response_messages();
        
        add_action( 'wp_ajax_nopriv_facebook_login', array( $this, 'facebook_login' ) );
        
    }
    
    
    
    public function render_button() {
        
        //if those twos are empty, there is no make sense to proceed, we are not even show the button
        if ( empty( $this->client_id ) || empty( $this->client_secret ) ) {
            return;
        }
        
        ?>
        <div id="face-button" class="contributer-connect contributer-facebook-login-button">
            <svg xmlns="http://www.w3.org/2000/svg" version="1.1" class="facebook-logo" x="0px" y="0px" viewBox="0 0 113.62199 218.79501">
                <path id="f" d="m 73.750992,218.795 v -99.803 h 33.498998 l 5.016,-38.895 H 73.750992 V 55.265 c 0,-11.261 3.127,-18.935 19.275,-18.935 L 113.62199,36.321 V 1.533 C 110.05999,1.059 97.833992,0 83.609992,0 c -29.695,0 -50.025,18.126 -50.025,51.413 V 80.097 H -8.1786701e-6 v 38.895 H 33.584992 v 99.803 h 40.166 z" style="fill:#fff;" />
            </svg>
            <?php _e( 'Login with Facebook', CONTR_PLUGIN_SLUG ) ?>
        </div>
        <?php
    }
    
    
    
    public function facebook_login() {
        
        //if checking nonce fails, there is no need to proceed
        if ( ! check_ajax_referer( 'facebook-login' , 'facebook_login_nonce', false ) ) {
            $this->send_json_output( false,  $this->get_response_message( 'try_later' ) );
        }
        
        require Sensei_Options::get_instance()->get_option( 'plugin_dir' ) . '/framework/classes/facebook/facebook.php';
        
        //initialize facebook sdk
        $facebook = new Facebook(
            array(
                'appId' => $this->client_id,
                'secret' => $this->client_secret
            )
        );
        $fbuser = $facebook->getUser();
        
        if ( $fbuser ) {
            try {
                // Proceed knowing you have a logged in user who's authenticated.
                $me = $facebook->api('/me'); //user
            }
            catch ( FacebookApiException $e ) {
                $fbuser = null;
            }
        }
        
        if ( ! $fbuser ){
            $this->send_json_output( false, $this->get_response_message( 'facebook_user_failed' ) );
        }
        
        //user details
        $email = $me['email'];
        
        if ( email_exists( $email ) ) {
            $user_info = get_user_by( 'email', $email );
            wp_set_current_user( $user_info->ID, $user_info->user_login );
            wp_set_auth_cookie( $user_info->ID );
            do_action( 'wp_login', $user_info->user_login );
        }
        else {
            
            $random_password = wp_generate_password( 20 );
            $user_id = wp_create_user( $email, $random_password, $email );

            if ( ! is_wp_error( $user_id ) ) {
                $wp_user_object = new WP_User( $user_id );
                $wp_user_object->set_role('subscriber');
                
                $creds['user_login'] = $email;
                $creds['user_password'] = $random_password;
                $creds['remember'] = false;
                $user = wp_signon( $creds, false );
                
                if ( is_wp_error( $user ) ) {
                    $this->send_json_output( false,  $user->get_error_message() );
                }
            }
            else {
                $this->send_json_output( false, $this->get_response_message( 'registration_failed' ) );
            }
        }
        
        //if we are here, we are in
        $return_array = array(
            'status' => true,
            'message' => ''
        );

        wp_send_json( $return_array );
    }
    
    
    
    private function get_response_message( $key ) {
        return $this->response_messages[ $key ];
    }
    
    
    
    public function populate_response_messages() {
        $this->response_messages = array(
            'facebook_user_failed' => __( 'We were not able to retrieve facebook user. Please try again.', CONTR_PLUGIN_SLUG ),
            'registration_failed' => __( 'Registration failed. Please try again.', CONTR_PLUGIN_SLUG ),
            'try_later' => __( 'Something wrong happened. Please try again later.', CONTR_PLUGIN_SLUG ),
            'registration_not_allowed' => __( 'Registration is not allowed at this moment. Please try again later.', CONTR_PLUGIN_SLUG )
        );
    }
    
    
    
    private function send_json_output( $status, $message ) {
        $return_array = array(
            'status' => $status,
            'message' => $message,
        );

        wp_send_json( $return_array );
    }
    
    
}

