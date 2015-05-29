<?php

require_once( 'login/class-facebook-login.php' );
require_once( 'login/class-google-login.php' );
require_once( 'login/class-email-login.php' );

//TODO: add more social logins, separate social logins into different classes. different arch7
//TODO: for ver2 add twitter login as well
class Contributer_Login {
	
    
    private $facebook_login = null;
    
    private $google_login = null;
    
    private $email_login = null;
    
    
    public function __construct() {
        
        //we will initialize these only if registration is allowed.
        if ( get_option( 'users_can_register' ) ) {
            $this->facebook_login = new Contributer_Facebook_Login();
            $this->google_login = new Contributer_Google_Login();
            $this->email_login = new Contributer_Email_Login();
        }
        
    }
    
    
    public function contributer_login() {
        if ( get_option( 'users_can_register' ) ) {
            return $this->contributer_login_allowed();
        }
        else {
            return $this->contributer_login_not_allowed();
        }
    }
    
    
    
    public function contributer_login_allowed() {
        
        ob_start();
        ?>

        <div class="contributer-signup">
            
            <div id="login-loader" class="overlay hidden_loader">
                <div class="loader">
                      <div class="ball"></div>
                      <div class="ball"></div>
                      <div class="ball"></div>
                      <span><?php _e( 'Please wait', CONTR_PLUGIN_SLUG ) ?></span>
                </div>
            </div>
            
            
            <p id="contributer-failure" class="message-handler contributer-failure"></p>
            <p id="contributer-success" class="message-handler contributer-success"></p>
            <p id="contributer-notification" class="message-handler contributer-notification"></p>
            
            <div class="login-container sign-toggle-container">
                
                <?php $this->facebook_login->render_button(); ?>

                <?php $this->google_login->render_button(); ?>
                
                <?php $this->email_login->render_sign_in_form(); ?>
                
            </div>
            
            <?php $this->email_login->render_sign_up_form(); ?>
            
        </div>
        <!-- contributer-signup end -->
        
        <?php
        $html_output = ob_get_clean();
        return $html_output;
    }
    
    
    
    public function contributer_login_not_allowed() {
        ob_start();
        ?>
        <div class="contributer-signup">
            <p><?php _e( 'This site does not allow new User Registration.', CONTR_PLUGIN_SLUG ) ?></p>
        </div>
        <?php
        $html_output = ob_get_clean();
        return $html_output;
    }

}
