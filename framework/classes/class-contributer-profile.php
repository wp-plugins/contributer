<?php

//TODO: If email is changed, send update to new email
//TODO: Implement possibility to add custom user fields dynamicly (for update and display)
class Contributer_Profile {
	
    public $user;
    
    private $update_response_messages = array();

    
    /**
     * Contributer Porofile constructore.
     * This class will handle updates related with profile (updating profile information)
     */
    public function __construct() {
        $this->update_response_messages();
        add_action( 'wp_ajax_update_profile', array( $this, 'ajax_update_profile' ) );
        add_action( 'wp_ajax_update_profile_image', array( $this, 'ajax_update_profile_image' ) );
    }
    
    
    
    /**
     * This method will populate $update_response_messages we are going to use when 
     * someone preform profile update. Placing those in separated method for easier maintainenance
     * 
     * TODO: Implement possibility to overide static messages/translations
     *       Add update messages options so user can populate them by himself using wp admin
     */
    public function update_response_messages() {
        $this->update_response_messages = array(
            'general_fail' => __( 'Something went wrong. Please try again later.', CONTR_PLUGIN_SLUG ),
            'invalid_email' => __( 'Invalid email. Please insert a valid email address and try again.', CONTR_PLUGIN_SLUG ),
            'email_exists' => __( 'The email you inserted already exists. Please choose another email address and try again.', CONTR_PLUGIN_SLUG ),
            'empty_display_name' => __( 'You did not insert a display name. Please insert your display name and try again.', CONTR_PLUGIN_SLUG ),
            'invalid_display_name' => __( 'Invalid display name. Please insert different display name and try again.', CONTR_PLUGIN_SLUG ),
            'invalid_url_site' => __( 'Invalid site URL. Please insert a valid site URL and try again.', CONTR_PLUGIN_SLUG ),
            'invalid_url_facebook' => __( 'Invalid facebook URL. Please insert a valid facebook URL and try again.', CONTR_PLUGIN_SLUG ),
            'invalid_url_twitter' => __( 'Invalid twitter URL. Please insert a valid twitter URL and try again.', CONTR_PLUGIN_SLUG ),
            'invalid_url_flickr' => __( 'Invalid flickr URL. Please insert a valid flickr URL and try again.', CONTR_PLUGIN_SLUG ),
            'user_updated' => __( 'User updated.', CONTR_PLUGIN_SLUG ),
            'upload_dir_permissions' => __( 'Upload directory is not writeable. Please contact an administrator.', CONTR_PLUGIN_SLUG ),
            'upload_service_down' => __( 'Uploading service is currently unavailable. Please try again later.', CONTR_PLUGIN_SLUG ),
            'image_uploaded' => __( 'Image successfully uploaded.', CONTR_PLUGIN_SLUG )
        );
    }
    
    
    
    private function get_response_message( $key ) {
        return $this->update_response_messages[ $key ];
    }

    
    
    /**
     * This method is going to be invoked by shortcode.
     * We are going to render profile page or login page if user is not logged in.
     * 
     * @return string(html content)
     */
    public function contributer_profile() {
        
        if ( is_user_logged_in() ) {
            $this->user = wp_get_current_user();
            return $this->render_contributer_profile();
        }
        else {
            $contributer_login_rendered = new Contributer_Login();
            return $contributer_login_rendered->contributer_login();
        }

    }
	
	
    
    /**
     * This method contains html content which we are going to render
     * when contributer_profile shortcode is used.
     * 
     * @return string - html content
     */
    public function render_contributer_profile() {

        $profile_image_id = get_user_meta( $this->user->ID, 'profile_image_attachment_id', true );
        if ( empty( $profile_image_id ) ) {
            $profile_image_url = CONTR_URL_PATH . '/assets/img/default-profile-pic.jpg'; 
        }
        else {
            $profile_image_url = wp_get_attachment_url( $profile_image_id  );
        }
        
        ob_start();
        ?>

        <div id="profile-loader" class="overlay hidden_loader">
            <div class="loader">
                  <div class="ball"></div>
                  <div class="ball"></div>
                  <div class="ball"></div>
                  <span><?php _e( 'Updating profile', CONTR_PLUGIN_SLUG ) ?></span>
            </div>
        </div>

        <p id="contributer-failure" class="message-handler contributer-failure"></p>
        <p id="contributer-success" class="message-handler contributer-success"></p>
        <p id="contributer-notification" class="message-handler contributer-notification"></p>

        <!-- profile pic starts-->
        <p class="contributer-profile-picture">
            <h2 class="contributer-title contributer-image-title">
                <?php _e( 'Profile Picture', CONTR_PLUGIN_SLUG ); ?>
            </h2>  
            <form id="file_form" action="" method="POST">
                <input type="hidden" name="action" value="update_profile_image">
                <?php wp_nonce_field( 'update-user-image-' . $this->user->ID, 'update_user_image_nonce' ); ?>
                <div class="profile-image-container">
                    <img id="profile-image" src="<?php echo $profile_image_url; ?>" />
                    <input type="file" id="profile-image-upload" name="profile-image-upload" class="hidden-upload">
                </div>
            </form>
            <p class="notice">
                <?php _e( 'Make sure to upload a square image for best-looking results.', CONTR_PLUGIN_SLUG ); ?>
            </p>
        </p>
        <!-- profile pic end-->

        <h2 class="contributer-title contributer-form-title">
            <?php _e( 'Profile Information', CONTR_PLUGIN_SLUG ); ?>
        </h2>
        <form id="profile-form" class="contributer-profile-container" method="POST" action="">

            <input type="hidden" name="action" value="update_profile" />
            <?php wp_nonce_field( 'update-user-' . $this->user->ID, 'update_user_nonce' ); ?>

            <p>
              <label for="bio"><?php _e( 'Bio', CONTR_PLUGIN_SLUG ); ?></label>
              <textarea name="bio" id="bio"><?php echo esc_html( $this->user->description ); ?></textarea>
            </p>

            <p>
              <label for="mail"><?php _e( 'Email', CONTR_PLUGIN_SLUG ); ?></label>
              <input id="mail" name="mail" required="required" value="<?php echo esc_attr( $this->user->user_email ); ?>" type="text"/>
            </p>

            <p>
              <label for="dn"><?php _e( 'Display Name', CONTR_PLUGIN_SLUG ); ?></label>
              <input id="dn" required="required" name="dn" type="text" value="<?php echo esc_attr( $this->user->display_name ); ?>" />
            </p>

            <p>
              <label for="site"><?php _e( 'Website URL', CONTR_PLUGIN_SLUG ); ?></label>
              <input id="site" name="site" type="text" value="<?php echo esc_attr( $this->user->user_url ); ?>" />
            </p>

            <p>
              <label for="facebook"><?php _e( 'Facebook URL', CONTR_PLUGIN_SLUG ); ?></label>
              <input id="facebook" name="facebook" type="text" value="<?php echo esc_attr( get_the_author_meta( 'facebook', $this->user->ID ) ); ?>" />
            </p>

            <p>
              <label for="twitter"><?php _e( 'Twitter URL', CONTR_PLUGIN_SLUG ); ?></label>
              <input id="twitter" name="twitter" type="text" value="<?php echo esc_attr( get_the_author_meta( 'twitter', $this->user->ID ) ); ?>" />
            </p>

            <p>
              <label for="flickr"><?php _e( 'Flickr URL', CONTR_PLUGIN_SLUG ); ?></label>
              <input id="flickr" name="flickr" type="text" value="<?php echo esc_attr( get_the_author_meta( 'flickr', $this->user->ID ) ); ?>" />
            </p>

            <p>
                <input type="submit" value="Save" />
            </p>

        </form>
        <!-- form alt end -->

        <?php
        $html_output = ob_get_clean();
        return $html_output;
    }
    
    
    
    /**
     * This method will validate email, and will return status/message/new email
     * 
     * @param string $old_email
     * @return array
     */
    private function check_email( $old_email ) {

        $email =  filter_input( INPUT_POST, 'mail', FILTER_VALIDATE_EMAIL );

        //check is email valid
        if ( FALSE === $email ) {
            return array( 
                'status' => false, 
                'message'=> $this->get_response_message( 'invalid_email' ) 
            );
        }

        //check does email already exists
        if ( $old_email != $email && email_exists( $email ) ) {
            return array( 
                'status' => false, 
                'message'=> $this->get_response_message( 'email_exists' )
            );
        }

        return array(
            'status' => true,
            'message' => '',
            'email' => $email
        );
    }
    
    
    
    /**
     * This method will validate display name we posted via ajax
     * 
     * @return array
     */
    private function check_display_name() {

        $display_name = filter_input( INPUT_POST, 'dn' );
        if ( empty( $display_name ) ) {
            return array( 
                'status' => false, 
                'message'=> $this->get_response_message( 'empty_display_name' ) 
            );
        }

        if ( ! preg_match('/^[a-z0-9 _@.\-]+$/i', $display_name ) ) {
            return array( 
                'status' => false, 
                'message'=> $this->get_response_message( 'invalid_display_name' ) 
            );
        }

        return array(
            'status' => true,
            'message' => '',
            'dn' => $display_name
        );

    }
    
    
    
    /**
     * This method will validate url.
     * 
     * TODO: Implement separate validation for each of the social links.
     * 
     * @param string $field_name
     * @return array
     */
    private function url_check( $field_name ) {

        $url = '';
        
        if ( ! empty( $_POST[ $field_name ] ) ) {
            
            $url = filter_input( INPUT_POST, $field_name, FILTER_VALIDATE_URL );

            //check is url valid
            if ( FALSE === $url ) {
                return array( 
                    'status' => false, 
                    'message'=> $this->get_response_message( 'invalid_url_' . $field_name )
                );
            }
        }
        
        return array(
                'status' => true,
                'message' => '',
                'url' => $url
        );

    }

    
    
    /************************ AJAX METHODS **************************************/
    /****************************************************************************/
    

    
    /**
     * Ajax method where we are handling profile update.
     */
    public function ajax_update_profile() {

        $email = '';
        $display_name = '';
        $website_url = '';
        $fb_url = '';
        $twitter_url = '';
        $flickr_url = '';
        $current_user = wp_get_current_user();
        
        //before everything, lets check nonce. If that fails do not proceed
        if ( ! check_ajax_referer( 'update-user-'. $current_user->ID , 'update_user_nonce', false ) ) {
            $this->send_json_output( false, $this->get_response_message( 'general_fail' ) );
        }

        //email check
        $email_check = $this->check_email( $current_user->user_email );
        if ( ! $email_check['status'] ) {
            $this->send_json_output( $email_check['status'], $email_check['message'] );
        }
        else {
            $email = $email_check['email'];
        }

        //display name check
        $displayname_check = $this->check_display_name();
        if ( ! $displayname_check['status'] ) {
            $this->send_json_output( $displayname_check['status'], $displayname_check['message'] );
        }
        else {
            $display_name = $displayname_check['dn'];
        }
        
        //website check
        $site_url_check = $this->url_check( 'site' );
        if ( ! $site_url_check['status'] ) {
            $this->send_json_output( $site_url_check['status'], $site_url_check['message'] );
        }
        else {
            $website_url = $site_url_check['url'];
        }
        
        //fb url check
        $fb_url_check = $this->url_check( 'facebook' );
        if ( ! $fb_url_check['status'] ) {
            $this->send_json_output( $fb_url_check['status'], $fb_url_check['message'] );
        }
        else {
            $fb_url = $fb_url_check['url'];
        }
        
        //twitter url check
        $twitter_url_check = $this->url_check( 'twitter' );
        if ( ! $twitter_url_check['status'] ) {
            $this->send_json_output( $twitter_url_check['status'], $twitter_url_check['message'] );
        }
        else {
            $twitter_url = $twitter_url_check['url'];
        }
        
        //twitter url check
        $flickr_url_check = $this->url_check( 'flickr' );
        if ( ! $flickr_url_check['status'] ) {
            $this->send_json_output( $flickr_url_check['status'], $flickr_url_check['message'] );
        }
        else {
            $flickr_url = $flickr_url_check['url'];
        }

        //saving user properties
        $update_user_properties = false;
        $args = array(
            'ID' => $current_user->ID,
        );

        //update only what changed
        if ( $current_user->user_email != $email ) {
            $update_user_properties = true;
            $args['user_email'] = $email;
        }

        if ( $current_user->display_name != $display_name ) {
            $update_user_properties = true;
            $args['display_name'] = $display_name;
        }
        
        if ( isset( $_POST['bio'] ) && $_POST['bio'] != $current_user->description ) {
            $update_user_properties = true;
            $args['description'] = wp_strip_all_tags( $_POST['bio'] );
        }
        
        if ( $website_url != $current_user->user_url ) {
            $update_user_properties = true;
            $args['user_url'] = $_POST['site'];
        }

        if ( $update_user_properties ) {
            wp_update_user( $args );
        }

        //saving user metadata
        update_user_meta( $current_user->ID, 'facebook', $fb_url ); 
        update_user_meta( $current_user->ID, 'twitter', $twitter_url ); 
        update_user_meta( $current_user->ID, 'flickr', $flickr_url ); 

        $this->send_json_output( true, $this->get_response_message( 'user_updated' ) );
    }
    
    

    //TODO: Improve image uploader. Consider using/adjusting third party scripts with possibility to show progress
    public function ajax_update_profile_image() {

        $status = true;
        $message = '';
        $image_url = '';
        $upload_dir = wp_upload_dir();
        global $wpdb;
        $current_user = wp_get_current_user();
        
        //before everything, lets check nonce. If that fails do not proceed
        if ( ! check_ajax_referer( 'update-user-image-'. $current_user->ID , 'update_user_image_nonce', false ) ) {
            $this->send_json_output( false, $this->get_response_message( 'general_fail' ) );
        }
        
        //checking ajax
        if ( ! ( is_array( $_POST ) && is_array( $_FILES ) ) ){
            return;
        }

        //loading wp_handle_upload if not
        if ( ! function_exists( 'wp_handle_upload' ) ){
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
        }
        
        if ( ! is_writeable( $upload_dir['path'] ) ) {
            $this->send_json_output( false, $this->get_response_message( 'upload_dir_permissions' ) );
        }
        
        foreach( $_FILES as $file ) {
            
            $file_info = wp_handle_upload( $file, array('test_form' => false, 'mimes' => array( 'gif' => 'image/gif', 'png' => 'image/png', 'jpg|jpeg|jpe' => 'image/jpeg' ) ) );

            if ( $file_info && ! isset( $file_info['error'] ) ) {
                $status = true;
                
                //resizing image
                $image = wp_get_image_editor( $file_info['file'] );
                if ( ! is_wp_error( $image ) ) {
                    $image->resize( 150, 150, true );
                    $image->save( $file_info['file'] );
                }
                
                $attachment = array(
                    'guid'           => $file_info['url'],
                    'post_mime_type' => $file_info['type'],
                    'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $file_info['file'] ) ),
                    'post_content'   => ""
                );
                
                if ( isset( $attachment['ID'] ) ) {
                  unset( $attachment['ID'] );
                }

                $attach_id = wp_insert_attachment( $attachment,  $file_info['file'] );
                
                if( ! is_wp_error( $attach_id ) ) {

                    $attach_metadata = wp_generate_attachment_metadata( $attach_id, $file_info['file'] );
                    wp_update_attachment_metadata( $attach_id, $attach_metadata );
                    update_user_meta( $current_user->ID, 'profile_image_attachment_id', $attach_id );
                    $image_url = $file_info['url'];
                    $message = $this->get_response_message( 'image_uploaded' );
                }
                else {
                    $status = false;
                    $message = $this->get_response_message( 'upload_service_down' );
                }
            }
            else {
                $status = false;
                $message = $file_info['error']; //non trans. error
            }
        }

        $return_array = array(
            'status' => $status,
            'message' => $message,
            'image_url' => $image_url
        );

        wp_send_json( $return_array );
    }
	
	
	
    private function send_json_output( $status, $message ) {
        $return_array = array(
            'status' => $status,
            'message' => $message,
        );

        wp_send_json( $return_array );
    }
    
}
