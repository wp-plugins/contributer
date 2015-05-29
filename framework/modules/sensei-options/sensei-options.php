<?php
/*
 * Using sensei options is really easy.
 * You just need to require this file inside your functions.php file or inside plugin files
 *
 * After files are included, you just need to call
 * new Sensei_Admin_Panel( $url, $args )
 * $url - this url represents an url location of Sensei_Admin_Panel. So url, where sensei-options.php file resides
 *        using this url we will be able to load css and js properly
 * $args - this is an array, which will have:
 *     $args['page'] - reprensets admin page parameters
 *     $args['tabs'] - tabs for that page
 *     $args['tabs']['options'] - options which will rside within specific tab
 *
 * There is no need to provide any kind of additional explanation, except to provide you demo of $args.
 * Enjoy!
 *
 */
define( 'SENSEI_OPTIONS_PLUGIN_SLUG', 'contributer_plugin'  );

require_once( 'class-sensei-options.php' );
require_once( 'class-sensei-options-renderer.php' );
require_once( 'class-sensei-admin-panel.php' );