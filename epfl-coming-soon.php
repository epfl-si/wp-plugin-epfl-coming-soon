<?php
/**
 * EPFL Coming Soon plugin

 * @package epfl-coming-soon
 */

/*
Plugin Name: EPFL Coming Soon
Plugin URI: https://github.com/epfl-si/wp-plugin-epfl-coming-soon
Description: EPFL coming soon / maintenance plugin
Author: EPFL SI
Version: 0.0.7
Author URI: https://github.com/epfl-si
*/

defined( 'ABSPATH' )
	|| die( 'Direct access not allowed.' );

define( 'EPFL_COMING_SOON_VERSION', '0.0.7' );

require_once WP_CONTENT_DIR . '/plugins/epfl-coming-soon/src/classes/class-epflcomingsoon.php';

/**
 * Init EPFL Coming Soon plugin
 */
function epfl_coming_soon_init() {
	$epfl_coming_soon = new EPFLComingSoon();
	$epfl_coming_soon->epfl_maintenance_load();
}
add_action( 'plugins_loaded', 'epfl_coming_soon_init' );
