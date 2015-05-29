<?php
/*
Plugin Name: Contributer
Plugin URI:  https://wordpress.org/plugins/contributer/
Description: Contributer enables your guests to easily create front-end post with videos, images or galleries. Transform your blog into a place of sharing and collaboration.
Author: digitalmind.ch
Version: 1.0
Author URI: http://digitalmind.ch
 */

//defining plugin path and plugin url
define( 'CONTR_DIR_PATH', plugin_dir_path( __FILE__ ) );
define( 'CONTR_URL_PATH', plugin_dir_url( __FILE__ )  );
define( 'CONTR_PLUGIN_SLUG', 'contributer_plugin'  );

//including modules
require_once( 'framework/modules/sensei-options/sensei-options.php' );
require_once( 'framework/modules/user-custom-fields/init.php' );

//including shortcode renderers
require_once( 'framework/classes/class-contributer-profile.php' );
require_once( 'framework/classes/class-contributer-contribute.php' );
require_once( 'framework/classes/class-contributer-login.php' );

//including other files
require_once( 'Contributer.php' );

new Contributer( __FILE__ );

