<?php

class Contributer_Email_Login {
    
    private $response_messages = array();
    
    
    
    public function __construct() {
        $this->populate_response_messages();
        
        add_action( 'wp_ajax_nopriv_email_login', array( $this, 'email_login' ) );
        add_action( 'wp_ajax_nopriv_email_sign_up', array( $this, 'email_sign_up' ) );
    }
    
    
    
    public function render_sign_in_form() {
        ?>
        <form id="email-sign-in">
            <input type="hidden" name="action" value="email_login" />
            <?php wp_nonce_field( 'email-login', 'email_login_nonce' ); ?>
            <p>
              <label for="username"><?php _e( 'Username', CONTR_PLUGIN_SLUG ) ?></label>
              <input id="username" name="username" required="required" type="text"/>
            </p>

            <p>
              <label for="password"><?php _e( 'Password', CONTR_PLUGIN_SLUG ) ?></label>
              <input id="password" name="password" required="required" type="password"/>
            </p>

            <input type="submit" value="<?php _e( 'Sign In', CONTR_PLUGIN_SLUG ) ?>"/>
        </form>

        <p>
            <?php _e( "Don't have an account yet?", CONTR_PLUGIN_SLUG ) ?> 
            <a href="#signup" class="signlink"><?php _e( 'Sign Up.', CONTR_PLUGIN_SLUG ) ?></a>
        </p>
        <?php
    }
    
    
    
    public function render_sign_up_form() {
        ?>
        <div class="signup-container sign-toggle-container">
            <form id="email-sign-up" >
                <input type="hidden" name="action" value="email_sign_up" />
                <?php wp_nonce_field( 'email-signup', 'email_signup_nonce' ); ?>
                <p>
                    <label for="email"><?php _e( 'E-Mail', CONTR_PLUGIN_SLUG ) ?></label>
                    <input id="email" name="email" required="required" type="text"/>
                </p>

                <p>
                    <label for="username"><?php _e( 'Username', CONTR_PLUGIN_SLUG ) ?></label>
                    <input id="username" name="username" required="required" type="text"/>
                </p>

                <p>
                    <label for="password"><?php _e( 'Password', CONTR_PLUGIN_SLUG ) ?></label>
                    <input id="password" name="password" required="required" type="password"/>
                </p>

                <p>
                    <label for="password2"><?php _e( 'Password again', CONTR_PLUGIN_SLUG ) ?></label>
                    <input id="password2" name="password2" required="required" type="password"/>
                </p>

                <input type="submit" value="<?php _e( 'Sign Up.', CONTR_PLUGIN_SLUG ) ?>"/>
            </form>

            <p>
                <?php _e( 'Already have an account?', CONTR_PLUGIN_SLUG ) ?> 
                <a href="#signin" class="signlink"><?php _e( 'Sign In.', CONTR_PLUGIN_SLUG ) ?></a>
            </p>
        </div>
        <?php
    }
    
    
    
    private function get_response_message( $key ) {
        return $this->response_messages[ $key ];
    }
    
    
    
    public function populate_response_messages() {
        $this->response_messages = array(
            'invalid_username_or_password' => __( 'Invalid username or password. Please try again.', CONTR_PLUGIN_SLUG ),
            'registration_failed' => __( 'Registration failed. Please try again.', CONTR_PLUGIN_SLUG ),
            'empty_email' => __( 'Email field is empty. Please insert email and try again.', CONTR_PLUGIN_SLUG ),
            'invalid_email' => __( 'Invalid email. Please insert valid email and try again.', CONTR_PLUGIN_SLUG ),
            'email_exists' => __( 'Email you inserted already exists. Please insert another email and try again.', CONTR_PLUGIN_SLUG ),
            'username_empty' => __( 'Username field is empty. Please insert username and try again.', CONTR_PLUGIN_SLUG ),
            'invalid_username' => __( 'Invalid username. Please try again.', CONTR_PLUGIN_SLUG ),
            'username_exists' => __( 'Username you inserted already exists. Please insert another username and try again.', CONTR_PLUGIN_SLUG ),
            'weak_password' => __( 'Your password needs to contain at least 4 characters.', CONTR_PLUGIN_SLUG ),
            'password_confirmation_fail' => __( 'The password and confirmation password do not match.', CONTR_PLUGIN_SLUG ),
            'try_later' => __( 'Something wrong happened. Please try again later.', CONTR_PLUGIN_SLUG )
        );
    }
    
    
    
    public function email_login() {
        
        //if checking nonce fails, there is no need to proceed
        if ( ! check_ajax_referer( 'email-login' , 'email_login_nonce', false ) ) {
            $this->send_json_output( false,  $this->get_response_message( 'try_later' ) );
        }
        
        $status = false;
        $remember_me = false;
        $message = '';

        if ( isset( $_POST['username'] ) ) {
            $username = $_POST['username'];
        }
        if ( isset($_POST['password'] ) ) {
            $password = $_POST['password'];
        }
        
        $creds = array();
        $creds['user_login'] = $username;
        $creds['user_password'] = $password;
        $creds['remember'] = $remember_me;
        $user_id = wp_signon( $creds, false );
        if( is_wp_error( $user_id ) ) {
            $status = false;
            $message = $this->get_response_message( 'invalid_username_or_password' );
        }
        else {
            $status = true;
        }
        $this->send_json_output( $status,  $message );
        
    }
    
    
    
    public function email_sign_up() {
        
        //if checking nonce fails, there is no need to proceed
        if ( ! check_ajax_referer( 'email-signup' , 'email_signup_nonce', false ) ) {
            $this->send_json_output( false,  $this->get_response_message( 'try_later' ) );
        }
        
        $message = '';
        $status = true;
        $username = '';
        $email = '';
        $password = '';
        $password2 = '';

        //email checks
        if ( isset( $_POST['email'] ) && ! empty( $_POST['email'] ) ) {
            $email = $_POST['email'];
        }
        else {
            $this->send_json_output( false, $this->get_response_message( 'empty_email' ) );
        }
        
        $email =  filter_input( INPUT_POST, 'email', FILTER_VALIDATE_EMAIL );

        if ( FALSE === $email ) {
            $this->send_json_output( false, $this->get_response_message( 'invalid_email' ) );
        }
        
        if ( email_exists( $email ) ) {
            $this->send_json_output( false, $this->get_response_message( 'email_exists' ) );
        }
        
        //username checks
        if ( isset( $_POST['username'] ) && ! empty( $_POST['username'] ) ) {
            $username = $_POST['username'];
        }
        else {
            $this->send_json_output( false, $this->get_response_message( 'username_empty' ) );
        }

        if ( ! validate_username( $username ) ) {
            $this->send_json_output( false, $this->get_response_message( 'invalid_username' ) );
        }
        
        if ( username_exists( $username ) ) {
            $this->send_json_output( false, $this->get_response_message( 'username_exists' ) );
        }
        
        //password checks
        if ( isset( $_POST['password'] ) && ! empty( $_POST['password'] ) && strlen( $_POST['password'] ) > 3 ) {
            $password = $_POST['password'];
        }
        else {
            $this->send_json_output( false, $this->get_response_message( 'weak_password' ) );
        }
        
        $password2 = $_POST['password2'];
        
        if ( $password != $password2 ) {
            $this->send_json_output( false, $this->get_response_message( 'password_confirmation_fail' ) );
        }
        
        //register user
    	$user_data = array(
            'user_login' => $username,
            'user_email' => $email,
            'user_pass' => $password,
        );
        $user_id = wp_create_user( $username, $password, $email );
    	if ( is_wp_error( $user_id ) ) {
           $this->send_json_output( false, $this->get_response_message( 'try_later' ) ); 
        } 
        else {
            $wp_user_object = new WP_User( $user_id );
            $wp_user_object->set_role('subscriber');
            $creds = array();
            $creds['user_login'] = $username;
            $creds['user_password'] = $password;
            $creds['remember'] = false;
            $user = wp_signon( $creds, false );
        }
                
        $this->send_json_output( true, '' );
    }
    
    
    
    private function send_json_output( $status, $message ) {
        $return_array = array(
            'status' => $status,
            'message' => $message,
        );

        wp_send_json( $return_array );
    }
    
    
}

