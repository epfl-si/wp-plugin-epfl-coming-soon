<?php
/*
Plugin Name: EPFL Coming Soon
Plugin URI: https://github.com/epfl-si/wp-plugin-epfl-coming-soon
Description: EPFL coming soon / maintenance plugin
Author: EPFL SI
Version: 0.0.3
Author URI: https://github.com/epfl-si
*/

defined('ABSPATH')
    or die('Direct access not allowed.');

define("EPFL_COMING_SOON_VERSION", "0.0.3");

require_once WP_CONTENT_DIR . '/plugins/epfl-coming-soon/src/classes/epfl-coming-soon.php';

function epfl_coming_soon_init() {
  $EPFLComingSoon = new EPFLComingSoon;
  $EPFLComingSoon->epfl_maintenance_load();
}
add_action('plugins_loaded', 'epfl_coming_soon_init');
