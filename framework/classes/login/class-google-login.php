<?php

//TODO: Make this class independent from the rest of the code (make module from it and move it to the module folder) 
class Contributer_Google_Login {
    
    private $client_id;
    
    private $client_secret;
    
   
    public function __construct() {
        $this->client_id = Sensei_Options::get_instance()->get_option( 'google_app_id' );
        $this->client_secret = Sensei_Options::get_instance()->get_option( 'google_app_secret' );
        
        //after everything loaded (mostly because user functons we are using)
        $this->google_login();
    }
    
    
    
    public function render_button() {
        ########## Google Settings.. Client ID, Client Secret from https://cloud.google.com/console #############
        
        //if those twos are empty, there is no make sense to proceed, we are not even show the button
        if ( empty( $this->client_id ) || empty( $this->client_secret ) ) {
            return;
        }
        
        //include google api files
        require_once Sensei_Options::get_instance()->get_option( 'plugin_dir' ) . '/framework/classes/google/autoload.php';
        
        $google_client = new Google_Client();
        $google_client->setApplicationName( 'Login to ' . home_url() );
        $google_client->setClientId( $this->client_id );
        $google_client->setClientSecret( $this->client_secret );
        $google_client->setRedirectUri( home_url() );
        $google_client->setScopes(
            array(
                'https://www.googleapis.com/auth/plus.login',
                'profile',
                'email',
                'openid',
           )
        );
        $auth_url = $google_client->createAuthUrl();
        
        ?>
        <a href="<?php echo $auth_url; ?>" class="contributer-connect contributer-google-login-button g-signin">
            <svg xmlns="http://www.w3.org/2000/svg" version="1.1" class="google-icon" x="0px" y="0px" viewBox="0 0 82.578992 84.937998">
                <g transform="translate(-26.927004,-23.354)">
                    <path d="m 70.479,71.845 -3.983,-3.093 c -1.213,-1.006 -2.872,-2.334 -2.872,-4.765 0,-2.441 1.659,-3.993 3.099,-5.43 4.64,-3.652 9.276,-7.539 9.276,-15.73 0,-8.423 -5.3,-12.854 -7.84,-14.956 h 6.849 l 7.189,-4.517 H 60.418 c -5.976,0 -14.588,1.414 -20.893,6.619 -4.752,4.1 -7.07,9.753 -7.07,14.842 0,8.639 6.633,17.396 18.346,17.396 1.106,0 2.316,-0.109 3.534,-0.222 -0.547,1.331 -1.1,2.439 -1.1,4.32 0,3.431 1.763,5.535 3.317,7.528 -4.977,0.342 -14.268,0.893 -21.117,5.103 -6.523,3.879 -8.508,9.525 -8.508,13.51 0,8.202 7.731,15.842 23.762,15.842 19.01,0 29.074,-10.519 29.074,-20.932 10e-4,-7.651 -4.419,-11.417 -9.284,-15.515 z M 56,59.107 c -9.51,0 -13.818,-12.294 -13.818,-19.712 0,-2.888 0.547,-5.87 2.428,-8.199 1.773,-2.218 4.861,-3.657 7.744,-3.657 9.168,0 13.923,12.404 13.923,20.382 0,1.996 -0.22,5.533 -2.762,8.09 -1.778,1.774 -4.753,3.096 -7.515,3.096 z m 0.109,44.543 c -11.826,0 -19.452,-5.657 -19.452,-13.523 0,-7.864 7.071,-10.524 9.504,-11.405 4.64,-1.561 10.611,-1.779 11.607,-1.779 1.105,0 1.658,0 2.538,0.111 8.407,5.983 12.056,8.965 12.056,14.629 0,6.859 -5.639,11.967 -16.253,11.967 z" style="fill:#fff;" />
                    <path d="m 98.393,58.938 0,-11.075 -5.47,0 0,11.075 -11.057,0 0,5.531 11.057,0 0,11.143 5.47,0 0,-11.143 11.113,0 0,-5.531 z" style="fill:#fff;" />
                </g>
            </svg>
            <?php _e( 'Login with Google', CONTR_PLUGIN_SLUG ) ?>
        </a>
        <?php
    }
    
    
    
    public function google_login() {
        
        //if those twos are empty, there is no make sense to proceed, we are not even show the button
        if ( empty( $this->client_id ) || empty( $this->client_secret ) ) {
            return;
        }
        
        $code = '';
        //if code is not provided no make sense to proceed with authentication
        if (  isset( $_GET['code'] ) && ! empty( $_GET['code'] ) ) { 
            $code = $_GET['code'];
        }
        else {
            return;
        }
        
        require_once Sensei_Options::get_instance()->get_option( 'plugin_dir' ) . '/framework/classes/google/autoload.php';
        require_once Sensei_Options::get_instance()->get_option( 'plugin_dir' ) . '/framework/classes/google/Service/Oauth2.php';
        
        $google_client = new Google_Client();
        $google_client->setApplicationName( 'Login to ' . home_url() );
        $google_client->setClientId( $this->client_id );
        $google_client->setClientSecret( $this->client_secret );
        $google_client->setRedirectUri( home_url() );
        $google_client->setScopes(array(
            'https://www.googleapis.com/auth/plus.login',
            'profile',
            'email',
            'openid',
        ));

        $google_oauth_v2 = new Google_Service_OAuth2( $google_client );
        
        $google_client->authenticate( $code );
        
        if ( $google_client->getAccessToken() ) {
            //For logged in user, get details from google using access token
            $user = $google_oauth_v2->userinfo->get();
            $email = filter_var( $user['email'], FILTER_SANITIZE_EMAIL );

            //preform registration
            if ( email_exists( $email ) ) {
                
                $user_info = get_user_by( 'email', $email );
                wp_set_current_user( $user_info->ID, $user_info->user_login );
                wp_set_auth_cookie( $user_info->ID );
                do_action( 'wp_login', $user_info->user_login );
                wp_redirect( Sensei_Options::get_instance()->get_option( 'redirect_login_url' ) ); exit;
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

                    if ( ! is_wp_error( $user ) ) {
                        wp_redirect( Sensei_Options::get_instance()->get_option( 'redirect_login_url' ) ); exit;
                    }
                }
            }  
        }
    }
    
    
}

