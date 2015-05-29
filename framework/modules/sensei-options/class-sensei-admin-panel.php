<?php

class Sensei_Admin_Panel {

    private $url;

    private $menu_page_parameters = array();

    private $tabs_parameters = array();

    
    /**
     * This is where everything starts.
     * In order to create page/options user needs to create instance of this class
     * 
     * @param string $url
     * @param array $args
     */
    public function __construct( $url, $args ) {
        //TODO: implement contruct parameters validation (before we assign vars as class properties. If validation fail, we are not going to add 'menu_page' action
        $this->url = $url;
        $this->menu_page_parameters = $args['page'];
        $this->tabs_parameters = $args['tabs'];
        Sensei_Options::get_instance( $args['tabs'], $this->menu_page_parameters['menu_slug'] );
        add_action( 'admin_menu', array( $this, 'register_menu_page' ) );
    }


    /**
     * Registering menu page and adding action for loading scripts
     */
    public function register_menu_page() {
        $sensei_admin_page = add_menu_page(
            $this->menu_page_parameters['page_title'],
            $this->menu_page_parameters['menu_title'],
            $this->menu_page_parameters['capability'],
            $this->menu_page_parameters['menu_slug'],
            array( $this, 'render_page' ),
            $this->menu_page_parameters['icon_url']
        );

        add_action( 'load-' . $sensei_admin_page, array( $this, 'sensei_scripts_loader' ) );
    }


    
    public function sensei_scripts_loader() {
        add_action( 'admin_enqueue_scripts', array( $this, 'sensei_admin_js' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'sensei_admin_css' ) );
    }


    
    public function sensei_admin_js() {
        wp_enqueue_script( 'sensei-options', $this->url.'/js/sensei-options.js', array( 'jquery' ), '1.0' );
        wp_localize_script( 'sensei-options', 'sensei_js_object', array(
            'sensei_nonce_resetall' => wp_create_nonce( 'sensei-nonce-resetall' ),
            'sensei_message_are_you_sure_reset_tab' => __( 'Are you sure? This will reset all options for this tab.', SENSEI_OPTIONS_PLUGIN_SLUG ),
            'sensei_message_something_is_wrong' => __( 'Something is wrong. Please try again later.', SENSEI_OPTIONS_PLUGIN_SLUG ),
            'sensei_message_are_you_sure_reset' => __( 'Are you sure? This will reset all options.', SENSEI_OPTIONS_PLUGIN_SLUG ),
        ));
    }


    
    public function sensei_admin_css() {
        wp_enqueue_style( 'sensei-options', $this->url.'/css/sensei-options.css', false, '1.0' );
    }


    
    public function render_page() {
        ?>
        <div class="sensei-panel-container">

            <div class="sensei-tab-menu-container">
                <ul>
                    <?php $tab_class = 'tab-selected' ?>
                    <?php foreach ( $this->tabs_parameters as $tab ) { ?>
                        <li id="<?php echo sanitize_html_class( $tab['id'] ); ?>" class="sensei-tab <?php echo $tab_class; ?>" >
                            <span class="tab-icon"></span>
                            <span class="tab-title"><?php echo esc_html( $tab['title'] ); ?></span>
                        </li>
                        <?php $tab_class = ''; ?>
                    <?php } ?>
                </ul>
                <div class="clearfix"></div>
            </div>

            <div class="sensei-tab-content-container">
                <?php $visibility = 1; ?>
                <?php foreach ( $this->tabs_parameters as $tab ) { ?>				
                    <div id="tab-content-<?php echo sanitize_html_class( $tab['id'] ); ?>" class="tab-content" style="<?php if ( $visibility ) { ?> display:block;  <?php } ?>">
                        <form id="sensei-options-form-<?php echo sanitize_html_class( $tab['id'] ); ?>" class="sensei-options-form" >

                            <input type="hidden" name="tab" value="<?php echo $tab['id']; //xss ok ?>" >
                            <input type="hidden" name="action" value="save_options" />
                            <?php wp_nonce_field( 'sensei-options-nonce-'.$tab['id'], 'sensei_options_nonce_'.$tab['id'], false ); ?>

                            <?php 
                            foreach ( $tab['options'] as $option ) {
                                new Sensei_Options_Renderer( $option );
                            }
                            ?>

                            <div class="sensei-option-container">
                                <div class="spinner"></div>
                                <input type="submit" class="sensei-submit" name="save-<?php echo sanitize_key( $tab['id'] ); ?>" value="<?php  _e( 'Save', SENSEI_OPTIONS_PLUGIN_SLUG ); ?>" />
                                <div class="sensei-reset-buttons">
                                    <span class="sensei-reset-tab sensei-submit" data-tab="<?php echo sanitize_html_class( $tab['id'] ); ?>"><?php  _e( 'Reset tab', SENSEI_OPTIONS_PLUGIN_SLUG ); ?></span>
                                    <span class="sensei-submit sensei-reset-all" data-tab="<?php echo sanitize_html_class( $tab['id'] ); ?>"><?php  _e( 'Reset all', SENSEI_OPTIONS_PLUGIN_SLUG ); ?></span>
                                </div>
                                <div class="clear"></div>
                            </div>

                        </form>
                    </div>
                <?php $visibility = 0; } ?>
            </div>

        </div>
        <?php
    }
}

