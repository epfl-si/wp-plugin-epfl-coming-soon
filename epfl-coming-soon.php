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
 * Version: 0.1.6
 * Author URI: https://github.com/epfl-si
 * Text Domain: epfl-coming-soon
 * Domain Path: src/languages
 */

defined( 'ABSPATH' )
	|| die( 'Direct access not allowed.' );

define( 'EPFL_COMING_SOON_VERSION', '0.1.6' );

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

/**
 * Add endpoint to WordPress REST API -> /wp-json/epfl-coming-soon/v1/status
 */
add_action( 'rest_api_init', function() {
    register_rest_route( 'epfl-coming-soon/v1', 'status', array(
        'method'   => 'WP_REST_Server::READABLE',
        'callback' => 'get_epfl_coming_soon_status',
        'permission_callback' => '__return_true',
    ) );
} );

function get_epfl_coming_soon_status() {
    $status = get_option('epfl_csp_options')['status'] ? '1' : '0';
    return $status;
} 
