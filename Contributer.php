<?php

class Contributer {

    private $plugin_directory;

    private $plugin_url;


    public function __construct( $file ) {

        $this->plugin_directory = plugin_dir_path( $file );
        $this->plugin_directory_rel = dirname( plugin_basename( $file ) );
        $this->plugin_url = plugin_dir_url( $file );
        
        add_action( 'init', array( $this, 'load_textdomain' ) );
        
        //enque js scripts
        add_action( 'wp_enqueue_scripts', array( $this, 'load_js' ) );

        //enqeue css styles
        add_action( 'wp_enqueue_scripts', array( $this, 'load_css' ) );

        //add filter for custom avatars
        add_filter( 'get_avatar' , array( $this, 'contributer_avatar' ) , 1 , 5 );
        
        //loading core on init.
        add_action( 'init', array( $this, 'load_plugin' ) );
    }
    
    
    
    public function load_plugin() {
        new Sensei_Admin_Panel( $this->plugin_url.'/framework/modules/sensei-options', $this->define_page_options( $this->plugin_directory ) );
        Sensei_Options::get_instance()->set_option( 'plugin_dir', $this->plugin_directory );

        $login_renderer = new Contributer_Login( $this->plugin_directory );
        add_shortcode( 'contributer_login', array( $login_renderer, 'contributer_login' ) );

        $profile_renderer = new Contributer_Profile();
        add_shortcode( 'contributer_profile', array( $profile_renderer, 'contributer_profile' ) );

        $contribute_renderer = new Contributer_Contribute();
        add_shortcode( 'contributer_contribute', array( $contribute_renderer, 'contributer_contribute' ) );

        $this->register_user_custom_fields();
    }

       
    
    /**
     * Load plugin textdomain.
     */
    public function load_textdomain() {
        load_plugin_textdomain( CONTR_PLUGIN_SLUG, false, $this->plugin_directory_rel . '/languages/' ); 
    }
    
    
        
    public function contributer_avatar( $avatar, $id_or_email, $size, $default, $alt ) {
        $user = false;

        if ( is_numeric( $id_or_email ) ) {
            $id = (int) $id_or_email;
            $user = get_user_by( 'id' , $id );
        } 
        elseif ( is_object( $id_or_email ) ) {
            $id_or_email_reference_check = $id_or_email->user_id;
            if ( ! empty( $id_or_email_reference_check ) ) {
                $id = (int) $id_or_email->user_id;
                $user = get_user_by( 'id' , $id );
            }
        }
        else {
            $user = get_user_by( 'email', $id_or_email );	
        }

        if ( $user && is_object( $user ) ) {
            $profile_image_id = get_user_meta( $user->ID, 'profile_image_attachment_id', true );
            if ( empty( $profile_image_id ) ) {
                $avatar = CONTR_URL_PATH . '/assets/img/default-profile-pic.jpg'; 
            }
            else {
                $avatar = wp_get_attachment_url( $profile_image_id  );
            }
            $avatar = "<img alt='{$alt}' src='{$avatar}' class='avatar avatar-{$size} photo' height='{$size}' width='{$size}' />";
        }

        return $avatar;
    }


    
    public function load_css() {
        wp_enqueue_style( 'contributer_login', $this->plugin_url.'/assets/css/main.css', false, '1.0' );
    }


    
    public function load_js() {

        if ( is_user_logged_in() ) {
            wp_enqueue_script( 'contributer_main', $this->plugin_url.'/assets/js/main.js', array( 'jquery', 'jquery-form' ), '1.0', true );
            wp_localize_script( 'contributer_main', 'contributer_object', array(
                'ajaxurl' => admin_url( 'admin-ajax.php' ),
                'logged_off_with_recaptcha' => false
            ));
        }
        else {
            
            $logged_off_with_recaptcha = false;
            $sensei_instance = Sensei_Options::get_instance();
            $google_recaptcha_secret_key = $sensei_instance->get_option( 'google_recaptcha_secret_key' );
            $google_recaptcha_site_key = $sensei_instance->get_option( 'google_recaptcha_site_key' );
            if (
                $sensei_instance->get_option( 'post_publish_without_registration' ) &&
                ! empty( $google_recaptcha_secret_key ) &&
                ! empty( $google_recaptcha_site_key )
            ) {
                $logged_off_with_recaptcha = true;
            }
            
            wp_enqueue_script( 'contributer_login', $this->plugin_url.'/assets/js/login.js', array( 'jquery' ), '1.0', true );
            wp_localize_script( 'contributer_login', 'contributer_object', array(
                'ajaxurl' => admin_url( 'admin-ajax.php' ),
                'redirect_login_url' => Sensei_Options::get_instance()->get_option( 'redirect_login_url' ),
                'facebook_app_id' => Sensei_Options::get_instance()->get_option( 'facebook_app_id' ),
                'google_app_id' => Sensei_Options::get_instance()->get_option( 'google_app_id' ),
                'facebook_login_nonce' => wp_create_nonce( 'facebook-login' ),
                'logged_off_with_recaptcha' => $logged_off_with_recaptcha
            ));
            
            if ( $sensei_instance->get_option( 'post_publish_without_registration' ) ) {
                wp_enqueue_script( 'contributer_main', $this->plugin_url.'/assets/js/main.js', array( 'jquery', 'jquery-form' ), '1.0', true );
            }
        }
    }
	
    
    //enable condition checks and val expr checks in v2 for only settings page.
    //currently it is executing for each call
    public function define_page_options() {
        
        $users = get_users( array( 'role' => 'administrator' ) );
        $users_array = array();
        foreach ( $users as $user ) {
            $users_array[ $user->ID ] = $user->user_login;
        }
        reset( $users_array );
        
        return array(
            'page' => array(
                'page_title' => __( 'Contributer Panel', CONTR_PLUGIN_SLUG ),
                'menu_title' => __( 'Contributer Panel', CONTR_PLUGIN_SLUG ),
                'capability' => 'manage_options',
                'menu_slug' => 'contributer',
                'icon_url' => false,
            ),
            'tabs' => array(
                //tab general
                array(
                    'title' => __( 'General', CONTR_PLUGIN_SLUG ),
                    'id' => 'login',
                    'icon' => '',
                    'options' => array(
                        array(
                            'name' => __( 'Redirect after login', CONTR_PLUGIN_SLUG ),
                            'id' => 'redirect_login_url',
                            'desc'  => __( 'Redirect url is place where user will be transfered after loggin is successfull. Homepage is default.', CONTR_PLUGIN_SLUG ),
                            'type'  => 'text',
                            'value'   => home_url(),
                        ),
                        array(
                            'name' => __( 'Allow post publishing without registration/login', CONTR_PLUGIN_SLUG ),
                            'id' => 'post_publish_without_registration',
                            'desc'  => __( 'Allow post publishing without registration/login', CONTR_PLUGIN_SLUG ),
                            'type'  => 'checkbox',
                            'value'   => false,
                        ),
                        array(
                            'name' => __( 'Assign Author', CONTR_PLUGIN_SLUG ),
                            'id' => 'guest_post_author',
                            'type' => 'select',
                            'desc' => __( 'Assign Author', CONTR_PLUGIN_SLUG ),
                            'options' => $users_array,
                            'value' => key( $users_array ),
                            'condition' => array(
                                'type' => 'option',
                                'value' => 'post_publish_without_registration'
                            )
                        ),
                        array(
                            'name' => __( 'Google reCaptcha Site Key', CONTR_PLUGIN_SLUG ),
                            'id' => 'google_recaptcha_site_key',
                            'desc'  => __( 'Strongly recommended to use google reCapcha if you want to allow public posting. https://www.google.com/recaptcha/admin', CONTR_PLUGIN_SLUG ),
                            'type'  => 'text',
                            'value'   => '',
                            'condition' => array(
                                'type' => 'option',
                                'value' => 'post_publish_without_registration'
                            )
                        ),
                        array(
                            'name' => __( 'Google reCaptcha Secret Key', CONTR_PLUGIN_SLUG ),
                            'id' => 'google_recaptcha_secret_key',
                            'desc'  => __( 'Strongly recommended to use google reCapcha if you want to allow public posting. https://www.google.com/recaptcha/admin', CONTR_PLUGIN_SLUG ),
                            'type'  => 'text',
                            'value'   => '',
                            'condition' => array(
                                'type' => 'option',
                                'value' => 'post_publish_without_registration'
                            )
                        ),
                        array(
                            'name' => __( 'Embed video into the content', CONTR_PLUGIN_SLUG ),
                            'id' => 'embed_video_into_content',
                            'desc'  => __( 'Activate this if your theme&apos;s video post type does not feature a "video URL" custom field', CONTR_PLUGIN_SLUG ),
                            'type'  => 'checkbox',
                            'value'   => true,
                            'condition' => array(
                                'type' => 'custom',
                                'value' => array( $this, 'video_format_exists' ),
                                'disabled_type' => 'hidden'
                            )
                        ),
                        
                    )
                ),
                //tab registration
                array(
                    'title' => __( 'Socials', CONTR_PLUGIN_SLUG ),
                    'id' => 'socials',
                    'icon' => '',
                    'options' => array(
                        array(
                            'name' => __( 'Facebok APP id', CONTR_PLUGIN_SLUG ),
                            'id' => 'facebook_app_id',
                            'desc'  => __( 'Please insert your facebook app id if you want to use facebook login.', CONTR_PLUGIN_SLUG ),
                            'type'  => 'text',
                            'value'   => ''
                        ),
                        array(
                            'name' => __( 'Facebok APP secret', CONTR_PLUGIN_SLUG ),
                            'id' => 'facebook_app_secret',
                            'desc'  =>  __( 'Please insert your facebook app secret if you want to use facebook login.', CONTR_PLUGIN_SLUG ),
                            'type'  => 'text',
                            'value'   => ''
                        ),
                        array(
                            'name' => __( 'Google APP id', CONTR_PLUGIN_SLUG ),
                            'id' => 'google_app_id',
                            'desc'  => __( 'Please insert your google app id if you want to use google+ login.', CONTR_PLUGIN_SLUG ),
                            'type'  => 'text',
                            'value'   => ''
                        ),
                        array(
                            'name' => __( 'Google APP secret', CONTR_PLUGIN_SLUG ),
                            'id' => 'google_app_secret',
                            'desc'  => __( 'Please insert your google app secret if you want to use google+ login.', CONTR_PLUGIN_SLUG ),
                            'type'  => 'text',
                            'value'   => ''
                        ),
                    )
                ),
                //tab shortcodes
                array(
                    'title' => __( 'Shortcodes', CONTR_PLUGIN_SLUG ),
                    'id' => 'shortcodes',
                    'icon' => '',
                    'options' => array(
                        array(
                            'name' => __( 'Profile Page', CONTR_PLUGIN_SLUG ),
                            'id' => 'profile_page_shortcode',
                            'desc'  => __( 'Copy &amp; Paste this shortcode into the place where you want the profile settings page to be rendered', CONTR_PLUGIN_SLUG ),
                            'type'  => 'text',
                            'value'   => '[contributer_profile]',
                        ),
                        array(
                            'name' => __( 'Contributing Page', CONTR_PLUGIN_SLUG ),
                            'id' => 'contribute_shortcode',
                            'desc'  => __( 'Copy &amp; Paste this shortcode into the place where you want the contributing form to be rendered', CONTR_PLUGIN_SLUG ),
                            'type'  => 'text',
                            'value'   => '[contributer_contribute]',
                        ),
                    )
                ),
            )
        );
    }


    private function register_user_custom_fields() {
        $fields = array( 
            array(
                'title' => 'Social Links',
                'fields' => array(
                    array(
                        'label' => 'Facebook',
                        'id' => 'facebook',
                        'type' => 'text',
                        'desc' => 'Please enter your facebook link.'
                    ),
                    array(
                        'label' => 'Twitter',
                        'id' => 'twitter',
                        'type' => 'text',
                        'desc' => 'Please enter your twitter link.'
                    ),
                    array(
                        'label' => 'Flickr',
                        'id' => 'flickr',
                        'type' => 'text',
                        'desc' => 'Please enter your flickr link.'
                    ),
                ),
            ), 
        );
        new User_Custom_Fields( $fields );
    }
    
    
    public function video_format_exists() {
        $post_formats = get_theme_support( 'post-formats' );
        
        if ( ! is_array( $post_formats[0] ) || empty( $post_formats[0] ) ) {
            return;
        }
        
        if ( in_array( 'video', $post_formats[0] ) ) {
            return true;
        }
        else {
            return false;
        }
    }

}
