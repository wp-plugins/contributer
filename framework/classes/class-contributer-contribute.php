<?php

//TODO: upload_images for helpers needs to be more cleaner. Separate it on several methods. Reuse logic.
//TODO: Separate on 2 classes, for logged in and not logged in posting.

class Contributer_Contribute {
    
    private $update_response_messages = array();
    
    
    public function __construct() {
        $this->update_response_messages();
        add_action( 'wp_ajax_add_post', array( $this, 'add_post' ) );
        if (  Sensei_Options::get_instance()->get_option( 'post_publish_without_registration' ) ) {
            add_action( 'wp_ajax_nopriv_add_post_logged_off', array( $this, 'add_post_logged_off' ) );
        }
    }
	

    
    public function contributer_contribute() {

        if ( is_user_logged_in() ) {
            return $this->render_contributer_contribute();
        }
        else {
            if ( Sensei_Options::get_instance()->get_option( 'post_publish_without_registration' ) ) {
                echo $this->render_contributer_contribute( false );
            }
            else {
                $contributer_login_rendered = new Contributer_Login();
                return $contributer_login_rendered->contributer_login();
            }
        }

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
            'general_fail' => __( 'You are not allowed to add post. Please try again later.', CONTR_PLUGIN_SLUG ),
            'wrong_captcha' => __( 'The enetered captcha was not correct. Please try again', CONTR_PLUGIN_SLUG ),
        );
    }
    
    
    
    private function get_response_message( $key ) {
        return $this->update_response_messages[ $key ];
    }

    

    public function render_contributer_contribute( $logged_in = true ) {

        ob_start();
        ?>

        <div id="publish-loader" class="overlay hidden_loader">
            <div class="loader">
                  <div class="ball"></div>
                  <div class="ball"></div>
                  <div class="ball"></div>
                  <span><?php _e( 'Saving', CONTR_PLUGIN_SLUG ); ?></span>
            </div>
        </div>

        <p id="contributer-failure" class="message-handler contributer-failure"></p>
        <p id="contributer-success" class="message-handler contributer-success"></p>
        <p id="contributer-notification" class="message-handler contributer-notification"></p>

        <form id="contributer-editor" class="contributer-editor">

            <?php if ( $logged_in ) { ?>
                <input type="hidden" id="action" name="action" value="add_post" />            
                <?php 
                    $current_user = wp_get_current_user();
                    wp_nonce_field( 'add-post-' . $current_user->ID, 'add_post_nonce' ); 
                ?>
            <?php } else { ?>
                <input type="hidden" id="action" name="action" value="add_post_logged_off" />
                <?php wp_nonce_field( 'add-post-logged-off', 'add_post_logged_off_nonce' );  ?>
            <?php } ?>

            <!-- post title -->
            <p>
                <label for="title"><?php _e( 'Title', CONTR_PLUGIN_SLUG ); ?></label>
                <input id="title" name="title" type="text" value="" />
            </p>
		
            <!-- post formats -->
            <?php
            //TODO: Support all default formats (maybe to add field for format recognition within settings page)
            $plugin_supported_formats = array( 'image', 'video', 'gallery' );

            if ( current_theme_supports( 'post-formats' ) ) {
                $post_formats = get_theme_support( 'post-formats' );

                if ( is_array( $post_formats[0] ) ) {
                    ?>
                    <p>
                        <span><?php _e( 'Post Format', CONTR_PLUGIN_SLUG ); ?></span>
                        <input id="standard" type="radio" name="post-format" value="standard" checked="checked" />
                        <label for="standard"><?php _e( 'Standard', CONTR_PLUGIN_SLUG ); ?></label>
                        <?php foreach ( $post_formats[0] as $post_format ) { ?>
                            <?php if ( in_array( $post_format, $plugin_supported_formats ) ) { ?>
                                <input id="<?php echo $post_format; ?>" type="radio" name="post-format" value="<?php echo $post_format; ?>"/>
                                <label for="<?php echo $post_format; ?>"><?php echo ucfirst( $post_format ); ?></label>
                            <?php } ?>
                        <?php } ?>
                    </p>
                    <?php
                }
            }
            ?>
		
            <!-- featured image -->
            <div id="feat-img-field" class="field">
                <span><?php _e( 'Featured image', CONTR_PLUGIN_SLUG ); ?></span>
                <div id="featured-image-upload-area" class="contributer-upload"> 
                    <div id="featured-image-upload-holder">
                        <div id="featured-image-uploaded"></div>
                        <div id="featured-image-upload-different"><?php _e( 'Click to select a different image', CONTR_PLUGIN_SLUG ); ?></div>
                    </div>
                    <p id="featured-image-upload-here"><?php _e( "drag 'n' drop", CONTR_PLUGIN_SLUG ); ?> <br/>
                        <input type="file" id="featured-image" name="featured-image" class="files" />
                    </p>
                </div>
            </div>
			
            <!-- gallery images -->
            <div id="gallery-field" class="field">
                <span><?php _e( 'Gallery images', CONTR_PLUGIN_SLUG ); ?></span>
                <div id="gallery-images-upload-area" class="contributer-upload">
                    <div id="gallery-images-upload-holder">
                        <div id="gallery-images-uploaded"></div>
                        <div id="gallery-images-upload-different"><?php _e( 'Click to select different images', CONTR_PLUGIN_SLUG ); ?></div>
                    </div>
                    <div id="gallery-images-upload-here"> 
                        <p><?php _e( "drag 'n' drop", CONTR_PLUGIN_SLUG ); ?> <br/>
                            <input type="file" id="gallery-images" name="gallery-images" class="files" multiple />
                        </p>
                    </div>
                </div>
            </div>
		
            <!-- post video url -->
            <p id="video-field" class="field">
                <label for="vid-url"><?php _e( 'Video URL', CONTR_PLUGIN_SLUG ); ?></label>
                <input id="vid-url" name="video_url" type="text"/>
            </p>
		
            <!-- post content -->
            <p>
                <label for="post-content"><?php _e( 'Content', CONTR_PLUGIN_SLUG ); ?></label>
                <?php
                wp_editor( '', 'post-content', array(
                    'wpautop'       => true,
                    'media_buttons' => false,
                    'textarea_name' => 'content',
                    'textarea_rows' => 10,
                    'teeny'         => true,
                ) );
                ?>
            </p>
            
            <!-- post tags -->
            <p>
                <label for="tags"><?php _e( 'Tags', CONTR_PLUGIN_SLUG ); ?></label>
                <input id="tags" type="text" name="tags" />
            </p>

            <!-- post category -->
            <p>
                <span><?php _e( 'Category', CONTR_PLUGIN_SLUG ); ?></span>
                <?php wp_dropdown_categories( array(
                        'hide_empty' => 0,  
                        'taxonomy' => 'category',
                        'orderby' => 'name', 
                        'hierarchical' => true, 
                        'show_option_none' => __( 'Choose your Category', CONTR_PLUGIN_SLUG ),
                        'name' => 'cat',
                        'id' => 'cat',
                        )
                ); ?>
            </p>
            
            <?php
            if ( ! $logged_in  ) {
                
                $google_recaptcha_secret_key = Sensei_Options::get_instance()->get_option( 'google_recaptcha_secret_key' );
                $google_recaptcha_site_key = Sensei_Options::get_instance()->get_option( 'google_recaptcha_site_key' );
                if ( ! empty( $google_recaptcha_site_key ) && ! empty( $google_recaptcha_secret_key ) ) {
                    require_once ( Sensei_Options::get_instance()->get_option( 'plugin_dir' ) . 'framework/modules/recaptcha/recaptchalib.php' );
                    echo recaptcha_get_html( $google_recaptcha_site_key );
                } 
            }
            ?>

            <input type="submit" value="<?php _e( 'Save draft', CONTR_PLUGIN_SLUG ); ?>"/>

        </form>
         <!-- form editor end -->

        <?php
        $html_output = ob_get_clean();
        return $html_output;
    }
	
	
    
    public function add_post() {

        $current_user = wp_get_current_user();
        //validate nonce. If that fails, do not proceed
        if ( ! check_ajax_referer( 'add-post-'. $current_user->ID , 'add_post_nonce', false ) ) {
            $this->send_json_output( false, $this->get_response_message( 'general_fail' ) );
        }
        
        $post_format = 'standard';
        $plugin_supported_formats = array( 'standard', 'video', 'image', 'gallery' );
        $post_formats = get_theme_support( 'post-formats' );
        
        if ( isset( $_POST['post-format'] ) && in_array( $_POST['post-format'], $plugin_supported_formats ) && in_array( $_POST['post-format'], $post_formats[0] ) ) {
            $post_format = $_POST['post-format'];
        }
        
        $class_name = 'CC' . ucfirst( $post_format ) . 'Format';
        $post_creator = new $class_name();
        $post_creator->insert_post();
    }
    
    
    
    public function add_post_logged_off() {
        
        //recapcha check if recapcha check exists
        $google_recaptcha_secret_key = Sensei_Options::get_instance()->get_option( 'google_recaptcha_secret_key' );
        $google_recaptcha_site_key = Sensei_Options::get_instance()->get_option( 'google_recaptcha_site_key' );
        if ( ! empty( $google_recaptcha_site_key ) && ! empty( $google_recaptcha_secret_key ) ) {
            require_once ( Sensei_Options::get_instance()->get_option( 'plugin_dir' ) . 'framework/modules/recaptcha/recaptchalib.php' );
            
            if ( isset( $_POST["recaptcha_challenge_field"] ) && isset( $_POST["recaptcha_response_field"] ) ) {
                $resp = recaptcha_check_answer ( $google_recaptcha_secret_key, $_SERVER["REMOTE_ADDR"], $_POST["recaptcha_challenge_field"], $_POST["recaptcha_response_field"] );
                if ( ! $resp->is_valid) {
                    $this->send_json_output( false, $this->get_response_message( 'wrong_captcha' ) );
                } 
            }
            else {
                $this->send_json_output( false, $this->get_response_message( 'wrong_captcha' ) );
            }
           
        }
        
        $post_format = 'standard';
        $plugin_supported_formats = array( 'standard', 'video', 'image', 'gallery' );
        $post_formats = get_theme_support( 'post-formats' );
        
        if ( isset( $_POST['post-format'] ) && in_array( $_POST['post-format'], $plugin_supported_formats ) && in_array( $_POST['post-format'], $post_formats[0] ) ) {
            $post_format = $_POST['post-format'];
        }
        
        $class_name = 'CC' . ucfirst( $post_format ) . 'Format';
        $post_creator = new $class_name();
        $post_creator->insert_post();
        
    }
    
    
    
    private function send_json_output( $status, $message ) {
        $return_array = array(
            'status' => $status,
            'message' => $message,
        );

        wp_send_json( $return_array );
    }
    
}



//CLASSES BELLOW ARE JUST LIKE HELPERS
//RIGHT NOW THEY ARE PRETTY MUCH THE SAME, BUT WE ARE GOING TO SEPARATE THEM LITTLE BT LATER BECAUSE
//DIFFERENCE WILL INCREASE WITH VERSIONS
/**
 * Standard format class
 */
class CCStandardFormat {
    
    private $post_title = '';
    
    private $post_content = '';
    
    private $post_category = array();
    
    private $post_tags = array();
    
    private $update_response_messages = null;
    
    
    public function __construct() {
        
        if ( isset( $_POST['title'] ) && ! empty( $_POST['title'] ) ) {
            $this->post_title = wp_strip_all_tags( $_POST['title'] );
        }

        if ( isset( $_POST['content'] ) && ! empty( $_POST['content'] ) ) {
            $this->post_content = strip_tags( $_POST['content'], '<strong><p><div><em><a><blockquote><del><ins><img><ul><li><ol><!--more--><code>' );
        }
        
        if ( isset( $_POST['cat'] ) && ! empty( $_POST['cat'] ) && $_POST['cat'] != -1 ) {
            $this->post_category = array( wp_strip_all_tags ( $_POST['cat'] ) );
        }

        if ( isset( $_POST['tags'] ) && ! empty( $_POST['tags'] ) ) {
            $this->post_tags = explode( ",", wp_strip_all_tags( $_POST['tags'] ) );
        }
        
        $this->update_response_messages = Add_Post_Response_Messages::get_instance();
        
    }
    
    
    public function insert_post() {

        $status = true;
        $message = '';
        
        if ( empty( $this->post_title ) ) {
            $this->send_json_output( false, $this->update_response_messages->get_response_message( 'empty_post_title' ) );
        }
        
        $content_to_validate = strip_tags( $this->post_content );
        if ( empty( $content_to_validate ) ) {
            $this->send_json_output( false, $this->update_response_messages->get_response_message( 'empty_post_content' ) );
        }
        
        if ( is_user_logged_in() ) {
           $user = wp_get_current_user(); 
        }
        else {
            $user = get_user_by( 'id', Sensei_Options::get_instance()->get_option( 'guest_post_author' ) );
        }
        
        
        $arguments = array(
            'post_content' => $this->post_content,
            'post_title' =>  $this->post_title,
            'post_status' => 'pending',
            'post_type' => 'post',
            'post_author' => $user->ID,
            'tags_input' => $this->post_tags,
            'post_category' => $this->post_category
        ); 

        $post_id = wp_insert_post( $arguments, true );
        
        if ( is_wp_error( $post_id ) ) {
            $status = false;
            $message = $post_id->get_error_message();
        }
        else {
            $upload_response = $this->upload_featured_image( $post_id );
            if ( $upload_response['status'] ) {
                $message = $this->update_response_messages->get_response_message( 'draft_saved' );
            }
            else {
                $message = $upload_response['message'];
            }
        }
        
        $return_array = array(
            'status' => $status,
            'message' => $message,
        );

        wp_send_json( $return_array );
        
    }
    
    
    
    private function upload_featured_image( $post_id ) {
        
        $return_array = array(
            'status' => true,
            'message' => ''
        );
        $wp_upload_dir = wp_upload_dir();
        
        //in this case we know that it will be only one
        foreach( $_FILES as $key => $file ) {
            
            if ( 'featured-image' != $key ) {
                continue;
            }
            
            $filename = $wp_upload_dir['path'] . '/' . basename( $post_id . '_' . $file['name'] );
            $filetype = wp_check_filetype( basename( $filename ), null );
            
            $attachment = array(
                'guid' => $wp_upload_dir['url'] . '/' . basename( $filename ), 
                'post_mime_type' => $filetype['type'],
                'post_title' => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
                'post_content' => '',
                'post_status' => 'inherit'
            );
            
            $upload = wp_handle_upload( $file, array( 'test_form' => false, 'mimes' => array( 'gif' => 'image/gif', 'png' => 'image/png', 'jpg|jpeg|jpe' => 'image/jpeg' ) ) );
            if ( $upload && ! isset( $upload['error'] ) ) {
                $attach_id = wp_insert_attachment( $attachment, $upload['file'] );
                $attach_data = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
                wp_update_attachment_metadata( $attach_id, $attach_data );
                add_post_meta( $post_id, '_thumbnail_id', $attach_id );
            }
            else {
                $return_array['status'] = false;
                $return_array['message'] = $this->update_response_messages->get_response_message( 'saved_with_warnings' ) . '<br />' . $file['name'] . '--' . $upload['error'];
            }
        } 
        
        return $return_array;
    }
    
    
    
    private function send_json_output( $status, $message ) {
        $return_array = array(
            'status' => $status,
            'message' => $message,
        );

        wp_send_json( $return_array );
    }
    
}



/**
 * Image format class
 */
class CCImageFormat {
    
    private $post_title = '';
    
    private $post_content = '';
    
    private $post_category = array();
    
    private $post_tags = array();
    
    private $update_response_messages = null;
    
    
    public function __construct() {
        
        if ( isset( $_POST['title'] ) && ! empty( $_POST['title'] ) ) {
            $this->post_title = wp_strip_all_tags( $_POST['title'] );
        }

        if ( isset( $_POST['content'] ) && ! empty( $_POST['content'] ) ) {
            $this->post_content = strip_tags( $_POST['content'], '<strong><p><div><em><a><blockquote><del><ins><img><ul><li><ol><!--more--><code>' );
        }
        
        if ( isset( $_POST['cat'] ) && ! empty( $_POST['cat'] ) && $_POST['cat'] != -1 ) {
            $this->post_category = array( wp_strip_all_tags ( $_POST['cat'] ) );
        }

        if ( isset( $_POST['tags'] ) && ! empty( $_POST['tags'] ) ) {
            $this->post_tags = explode( ",", wp_strip_all_tags( $_POST['tags'] ) );
        }
        
        $this->update_response_messages = Add_Post_Response_Messages::get_instance();

    }
    
    
    public function insert_post() {
        
        $status = true;
        $message = '';
        if ( is_user_logged_in() ) {
           $user = wp_get_current_user(); 
        }
        else {
            $user = get_user_by( 'id', Sensei_Options::get_instance()->get_option( 'guest_post_author' ) );
        }
        
        if ( empty( $this->post_title ) ) {
            $this->send_json_output( false, $this->update_response_messages->get_response_message( 'empty_post_title' ) );
        }
        
        if ( ! isset( $_FILES['featured-image'] ) ) {
            $this->send_json_output( false, $this->update_response_messages->get_response_message( 'featured_image_required' ) );
        } 
        
        $arguments = array(
            'post_content' => $this->post_content,
            'post_title' =>  $this->post_title,
            'post_status' => 'pending',
            'post_type' => 'post',
            'post_author' => $user->ID,
            'tags_input' => $this->post_tags,
            'post_category' => $this->post_category
        ); 

        $post_id = wp_insert_post( $arguments, true );
        
        if ( is_wp_error( $post_id ) ) {
            $status = false;
            $message = $post_id->get_error_message();
        }
        else {
            $upload_response = $this->upload_featured_image( $post_id );
            if ( $upload_response['status'] ) {
                set_post_format( $post_id, 'image' );
                $message = $this->update_response_messages->get_response_message( 'draft_saved' );
            }
            else {
                wp_delete_post( $post_id, true );
                $status = false;
                $message = $this->update_response_messages->get_response_message( 'upload_failed2' ) .'<br/ >';
            }
        }
        
        $return_array = array(
            'status' => $status,
            'message' => $message,
        );

        wp_send_json( $return_array );
        
    }
    
    
    
    private function upload_featured_image( $post_id ) {
        
        $return_array = array(
            'status' => true,
            'message' => ''
        );
        $wp_upload_dir = wp_upload_dir();
        
        //in this case we know that it will be only one
        foreach( $_FILES as $key => $file ) {
            
            if ( 'featured-image' != $key ) {
                continue;
            }
            
            $filename = $wp_upload_dir['path'] . '/' . basename( $post_id . '_' . $file['name'] );
            $filetype = wp_check_filetype( basename( $filename ), null );
            
            $attachment = array(
                'guid' => $wp_upload_dir['url'] . '/' . basename( $filename ), 
                'post_mime_type' => $filetype['type'],
                'post_title' => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
                'post_content' => '',
                'post_status' => 'inherit'
            );
            
            $upload = wp_handle_upload( $file, array( 'test_form' => false, 'mimes' => array( 'gif' => 'image/gif', 'png' => 'image/png', 'jpg|jpeg|jpe' => 'image/jpeg' ) ) );
            if ( $upload && ! isset( $upload['error'] ) ) {
                $attach_id = wp_insert_attachment( $attachment, $upload['file'] );
                $attach_data = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
                wp_update_attachment_metadata( $attach_id, $attach_data );
                add_post_meta( $post_id, '_thumbnail_id', $attach_id );
            }
            else {
                $return_array['status'] = false;
                $return_array['message'] = $upload['error'];
            }
        }
             
        return $return_array;
    }
    
    
    
    private function send_json_output( $status, $message ) {
        $return_array = array(
            'status' => $status,
            'message' => $message,
        );

        wp_send_json( $return_array );
    }
    
}



/**
 * Standard format class
 */
class CCVideoFormat {
    
    private $post_title = '';
    
    private $post_content = '';
    
    private $post_category = array();
    
    private $post_tags = array();
    
    private $video_url = '';
    
    private $update_response_messages = null;
    
    
    public function __construct() {
        
        if ( isset( $_POST['title'] ) && ! empty( $_POST['title'] ) ) {
            $this->post_title = wp_strip_all_tags( $_POST['title'] );
        }

        if ( isset( $_POST['content'] ) && ! empty( $_POST['content'] ) ) {
            $this->post_content = strip_tags( $_POST['content'], '<strong><p><div><em><a><blockquote><del><ins><img><ul><li><ol><!--more--><code>' );
        }
        
        if ( isset( $_POST['cat'] ) && ! empty( $_POST['cat'] ) && $_POST['cat'] != -1 ) {
            $this->post_category = array( wp_strip_all_tags ( $_POST['cat'] ) );
        }

        if ( isset( $_POST['tags'] ) && ! empty( $_POST['tags'] ) ) {
            $this->post_tags = explode( ",", wp_strip_all_tags( $_POST['tags'] ) );
        }
        
        if ( isset( $_POST['video_url'] ) && ! empty( $_POST['video_url'] ) ) {
            $this->video_url = wp_strip_all_tags( $_POST['video_url'] );
        }
        
        $this->update_response_messages = Add_Post_Response_Messages::get_instance();
        
    }
    
    
    public function insert_post() {
        
        if ( empty( $this->post_title ) ) {
            $this->send_json_output( false, $this->update_response_messages->get_response_message( 'empty_post_title' ) );
        }
        
        if ( empty( $this->video_url ) ) {
            $this->send_json_output( false, $this->update_response_messages->get_response_message( 'empty_video_url' ) );
        }
        
        if ( wp_oembed_get( $this->video_url ) === false ) {
            $this->send_json_output( false, $this->update_response_messages->get_response_message( 'invalid_video_url' ) );
        }
        
        $status = true;
        $message = '';
        
        if ( is_user_logged_in() ) {
           $user = wp_get_current_user(); 
        }
        else {
            $user = get_user_by( 'id', Sensei_Options::get_instance()->get_option( 'guest_post_author' ) );
        }
        
        $video_shortcode = '';
        if ( Sensei_Options::get_instance()->get_option( 'embed_video_into_content' ) ) {
           $video_shortcode = '[embed]' . $this->video_url . '[/embed]'; 
        }
        
        $arguments = array(
            'post_content' => $video_shortcode. ' <div>' . $this->post_content .'</div>',
            'post_title' =>  $this->post_title,
            'post_status' => 'pending',
            'post_type' => 'post',
            'post_author' => $user->ID,
            'tags_input' => $this->post_tags,
            'post_category' => $this->post_category
        ); 

        $post_id = wp_insert_post( $arguments, true );
        
        if ( is_wp_error( $post_id ) ) {
            $status = false;
            $message = $post_id->get_error_message();
        }
        else {
            $upload_response = $this->upload_featured_image( $post_id );
            set_post_format( $post_id, 'video' );
            update_post_meta( $post_id, 'video_url', $this->video_url );          
            if ( $upload_response['status'] ) {
                $message = $this->update_response_messages->get_response_message( 'draft_saved' );
            }
            else {
                $message = $upload_response['message'];
            }
        }
        
        $return_array = array(
            'status' => $status,
            'message' => $message,
        );

        wp_send_json( $return_array );
        
    }
    
    
    
    private function upload_featured_image( $post_id ) {
        
        $return_array = array(
            'status' => true,
            'message' => ''
        );
        $wp_upload_dir = wp_upload_dir();
        
        //in this case we know that it will be only one
        foreach( $_FILES as $key => $file ) {
            
            if ( 'featured-image' != $key ) {
                continue;
            }
            
            $filename = $wp_upload_dir['path'] . '/' . basename( $post_id . '_' . $file['name'] );
            $filetype = wp_check_filetype( basename( $filename ), null );
            
            $attachment = array(
                'guid' => $wp_upload_dir['url'] . '/' . basename( $filename ), 
                'post_mime_type' => $filetype['type'],
                'post_title' => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
                'post_content' => '',
                'post_status' => 'inherit'
            );
            
            $upload = wp_handle_upload( $file, array( 'test_form' => false, 'mimes' => array( 'gif' => 'image/gif', 'png' => 'image/png', 'jpg|jpeg|jpe' => 'image/jpeg' ) ) );
            if ( $upload && ! isset( $upload['error'] ) ) {
                $attach_id = wp_insert_attachment( $attachment, $upload['file'] );
                $attach_data = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
                wp_update_attachment_metadata( $attach_id, $attach_data );
                add_post_meta( $post_id, '_thumbnail_id', $attach_id );
            }
            else {
                $return_array['status'] = false;
                $return_array['message'] = $this->update_response_messages->get_response_message( 'saved_with_warnings' ) . $file['name'] . '--' . $upload['error'];
            }

        }
          
        return $return_array;
        
    }
    
    
    
    private function send_json_output( $status, $message ) {
        $return_array = array(
            'status' => $status,
            'message' => $message,
        );

        wp_send_json( $return_array );
    }
    
}



/**
 * Standard format class
 */
class CCGalleryFormat {
    
    private $post_title = '';
    
    private $post_content = '';
    
    private $post_category = array();
    
    private $post_tags = array();
    
    private $update_response_messages = null;
    
    
    public function __construct() {
        
        if ( isset( $_POST['title'] ) && ! empty( $_POST['title'] ) ) {
            $this->post_title = wp_strip_all_tags( $_POST['title'] );
        }

        if ( isset( $_POST['content'] ) && ! empty( $_POST['content'] ) ) {
            $this->post_content = strip_tags( $_POST['content'], '<strong><p><div><em><a><blockquote><del><ins><img><ul><li><ol><!--more--><code>' );
        }
        
        if ( isset( $_POST['cat'] ) && ! empty( $_POST['cat'] ) && $_POST['cat'] != -1 ) {
            $this->post_category = array( wp_strip_all_tags ( $_POST['cat'] ) );
        }

        if ( isset( $_POST['tags'] ) && ! empty( $_POST['tags'] ) ) {
            $this->post_tags = explode( ",", wp_strip_all_tags( $_POST['tags'] ) );
        }
        
        $this->update_response_messages = Add_Post_Response_Messages::get_instance();
        
    }
    
    
    
    public function insert_post() {
        
        //required, post title needs to be set
        if ( empty( $this->post_title ) ) {
            $this->send_json_output( false, $this->update_response_messages->get_response_message( 'empty_post_title' ) );
        }
        
        //required, at least one image needs to be set
        if ( ! isset( $_FILES['gallery-image-0'] ) ) {
            $this->send_json_output( false, $this->update_response_messages->get_response_message( 'gallery_image_missing' ) );
        }
        
        $status = true;
        $message = '';
        
        if ( is_user_logged_in() ) {
           $user = wp_get_current_user(); 
        }
        else {
            $user = get_user_by( 'id', Sensei_Options::get_instance()->get_option( 'guest_post_author' ) );
        }
        
        $arguments = array(
            'post_content' => $this->post_content,
            'post_title' =>  $this->post_title,
            'post_status' => 'pending',
            'post_type' => 'post',
            'post_author' => $user->ID,
            'tags_input' => $this->post_tags,
            'post_category' => $this->post_category
        ); 

        $post_id = wp_insert_post( $arguments, true );
        
        if ( is_wp_error( $post_id ) ) {
            $status = false;
            $message = $post_id->get_error_message();
        }
        else {
            
            //upload feature image
            $featured_image_array = $this->upload_featured_image( $post_id );

            //uploading gallery images
            $attachments_array = $this->upload_images( $post_id );  
            
            if ( $attachments_array['status'] ) {
                $additional_content = implode( ',', $attachments_array['attachments'] );
                set_post_format( $post_id, 'gallery' );

                $arguments = array(
                    'ID' => $post_id,
                    'post_content' => $this->post_content . '<br />' . '[gallery ids="' . $additional_content . '"]'
                ); 
                wp_update_post( $arguments, true );
                
                if ( ! empty ( $attachments_array['warning_message'] ) ) {
                    $message = $this->update_response_messages->get_response_message( 'saved_with_warnings' ) . ' <br />' . $attachments_array['warning_message'].$featured_image_array['message'];
                }
                else {
                    if ( $featured_image_array['status'] ) {
                        $message = $this->update_response_messages->get_response_message( 'saved' ) . '<br />';
                    }
                    else {
                        $message = $this->update_response_messages->get_response_message( 'saved_with_warnings' ) . ' <br />'.$featured_image_array['message'];
                    }
                }
            }
            else {
                $status = false;
                wp_delete_post( $post_id, true );
                $message = $attachments_array['error_message'];
            }
        }
        
        $return_array = array(
            'status' => $status,
            'message' => $message,
        );

        wp_send_json( $return_array );
        
    }
    
    
    
    private function upload_images( $post_id ) {
        
        $return_array = array(
            'error_message' => '',
            'warning_message' => '',
            'status' => true
        );
        $wp_upload_dir = wp_upload_dir();
        $attachments = array();
        $succeed_uploads = 0;
        
        for ( $i = 0; $i <= 20; $i++ ) {
            
            if ( ! isset( $_FILES['gallery-image-'.$i] ) ) {
                continue;
            }
            
            $file = $_FILES['gallery-image-'.$i];
            
            $filename = $wp_upload_dir['path'] . '/' . basename( $post_id . '_' . $i . '_' .  $file['name'] );
            $filetype = wp_check_filetype( basename( $filename ), null );
            
            $attachment = array(
                'guid' => $wp_upload_dir['url'] . '/' . basename( $filename ), 
                'post_mime_type' => $filetype['type'],
                'post_title' => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
                'post_content' => '',
                'post_status' => 'inherit'
            );
            
            $upload = wp_handle_upload( $file, array( 'test_form' => false, 'mimes' => array( 'gif' => 'image/gif', 'png' => 'image/png', 'jpg|jpeg|jpe' => 'image/jpeg' ) ) );
            if ( $upload && ! isset( $upload['error'] ) ) {
                $attach_id = wp_insert_attachment( $attachment, $upload['file'] );
                $attach_data = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
                wp_update_attachment_metadata( $attach_id, $attach_data );
                $attachments[] = $attach_id;
                $succeed_uploads++;
            }
            else {
                $return_array['warning_message'] = $return_array['warning_message'] . $this->update_response_messages->get_response_message( 'image' ) . ' ' . $_FILES['gallery-image-'.$i]['name'] . '--' . $upload['error'] . '<br />';
            }  
        }
        
        $return_array['attachments'] = $attachments;
        
        if ( $succeed_uploads == 0 ) {
            $return_array['error_message'] = $this->update_response_messages->get_response_message( 'upload_failed' );
            $return_array['status'] = false;
        }
        
        return $return_array;     
    }
    
    
    
    private function upload_featured_image( $post_id ) {
        
        $return_array = array(
            'status' => true,
            'message' => ''
        );
        $wp_upload_dir = wp_upload_dir();
        
        //in this case we know that it will be only one
        foreach( $_FILES as $key => $file ) {
            
            if ( 'featured-image' != $key ) {
                continue;
            }
            
            $filename = $wp_upload_dir['path'] . '/' . basename( $post_id . '_' . $file['name'] );
            $filetype = wp_check_filetype( basename( $filename ), null );
            
            $attachment = array(
                'guid' => $wp_upload_dir['url'] . '/' . basename( $filename ), 
                'post_mime_type' => $filetype['type'],
                'post_title' => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
                'post_content' => '',
                'post_status' => 'inherit'
            );
            
            $upload = wp_handle_upload( $file, array( 'test_form' => false, 'mimes' => array( 'gif' => 'image/gif', 'png' => 'image/png', 'jpg|jpeg|jpe' => 'image/jpeg' ) ) );
            if ( $upload && ! isset( $upload['error'] ) ) {
                $attach_id = wp_insert_attachment( $attachment, $upload['file'] );
                $attach_data = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
                wp_update_attachment_metadata( $attach_id, $attach_data );
                add_post_meta( $post_id, '_thumbnail_id', $attach_id );
            }
            else {
                $return_array['status'] = false;
                $return_array['message'] = $this->update_response_messages->get_response_message( 'featured_image' ) . ' ' . $file['name'] . '--' . $upload['error'] . '<br />';
            }

        }
        
        return $return_array;
                
    }
    
    
    
    private function send_json_output( $status, $message ) {
        $return_array = array(
            'status' => $status,
            'message' => $message,
        );

        wp_send_json( $return_array );
    }
    
}



class Add_Post_Response_Messages {
    
    private $add_post_response_messages = array();
    
    
    public function __construct() {
        $this->add_post_response_messages();
    }
    
    
    public static function get_instance(){
        global $add_post_response_messages;

        if ( null == $add_post_response_messages ) {
            $add_post_response_messages = new Add_Post_Response_Messages();
        }

        return $add_post_response_messages;
    }
    
    
    public function add_post_response_messages() {
        $this->add_post_response_messages = array(
            'general_fail' => __( 'You are not allowed to add post. Please try again later.', CONTR_PLUGIN_SLUG ),
            'empty_post_title' => __( 'Post title is empty. Please insert post title and try again.', CONTR_PLUGIN_SLUG ),
            'empty_post_content' => __( 'Post content is empty. Please insert post content and try again.', CONTR_PLUGIN_SLUG ),
            'gallery_image_missing' => __( 'You need to upload at least one image in order to publish a gallery.', CONTR_PLUGIN_SLUG ),
            'saved_with_warnings' => __( 'Post saved with warnings:', CONTR_PLUGIN_SLUG ),
            'saved' => __( 'Post saved', CONTR_PLUGIN_SLUG ),
            'upload_failed' => __( 'Upload of images failed', CONTR_PLUGIN_SLUG ),
            'upload_failed2' => __( 'We were not able to upload your image', CONTR_PLUGIN_SLUG ),
            'featured_image' => __( 'Featured image:', CONTR_PLUGIN_SLUG ),
            'image' => __( 'Image:', CONTR_PLUGIN_SLUG ),
            'draft_saved' => __( 'Your draft was saved and will be reviewed.', CONTR_PLUGIN_SLUG ),
            'invalid_video_url' => __( 'Invalid video url. Please insert valid video url and try again.', CONTR_PLUGIN_SLUG ),
            'empty_video_url' => __( 'Video url is empty. Please insert video url and try again.', CONTR_PLUGIN_SLUG ),
            'featured_image_required' => __( 'Featured image is required for image posts.', CONTR_PLUGIN_SLUG ),
        );
    }
    
    
    
    public function get_response_message( $key ) {
        return $this->add_post_response_messages[ $key ];
    }
    
    
    
}
