<?php
/**
 * EPFL Coming Soon plugin

 * @package epfl-coming-soon
 */

/*
 * Plugin Name: EPFL Coming Soon
 * Plugin URI: https://github.com/epfl-si/wp-plugin-epfl-coming-soon
 * Description: Coming Soon, Under Construction or Maintenance Mode plugin for WordPress done right.
 * Author: EPFL IDEV-FSD
 * Version: 0.1.4
 * Author URI: https://github.com/epfl-si
 * Text Domain: epfl-coming-soon
 * Domain Path: src/languages
 */

defined( 'ABSPATH' )
	|| die( 'Direct access not allowed.' );

define( 'EPFL_COMING_SOON_VERSION', '0.1.4' );

require_once WP_CONTENT_DIR . '/plugins/epfl-coming-soon/src/classes/class-epflcomingsoon.php';

/**
 * Init EPFL Coming Soon plugin
 */
function epfl_coming_soon_init() {
	$epfl_coming_soon = new EPFLComingSoon();
	$epfl_coming_soon->epfl_maintenance_load();
}
add_action( 'plugins_loaded', 'epfl_coming_soon_init' );

/**
 * Load epfl-coming-soon text domain
 */
function epfl_coming_soon_plugin_textdomain() {
	load_plugin_textdomain( 'epfl-coming-soon', false, 'epfl-coming-soon/src/languages/' );
}
add_action( 'init', 'epfl_coming_soon_plugin_textdomain' );
