<?php
/*
Plugin Name: EPFL Coming Soon
Plugin URI: https://github.com/epfl-si/wp-plugin-epfl-coming-soon
Description: EPFL coming soon / maintenance plugin
Author: EPFL SI
Version: 0.0.1
Author URI: https://github.com/epfl-si
*/

/*
TODOs:
  - check if .maintenance file is here (wp cli maintenance)
    See https://developer.wordpress.org/cli/commands/maintenance-mode/
  - check if the theme have a maintenance file
    Check box : use theme maintenance page if present ?
  - add rest API route
    version, status, etc..
  - coming soon is active
**/

defined('ABSPATH')
    or die('Direct access not allowed.');

function epfl_coming_soon_add_settings_page()
{
    add_options_page('EPFL Coming Soon', 'EPFL Coming Soon Settings', 'manage_options', 'epfl-coming-soon', 'epfl_coming_soon_render_plugin_settings_page');
}
add_action('admin_menu', 'epfl_coming_soon_add_settings_page');

function epfl_coming_soon_render_plugin_settings_page()
{
    ?>
    <h2>EPFL Coming Soon Settings</h2>
    <form action="options.php" method="post">
        <?php
        settings_fields('epfl_coming_soon_plugin_options');
        do_settings_sections('epfl_coming_soon_plugin'); ?>
        <input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e('Save'); ?>" />
    </form>
    <?php
}

// https://deliciousbrains.com/create-wordpress-plugin-settings-page/
function epfl_coming_soon_register_settings()
{
    register_setting('epfl_coming_soon_plugin_options', 'epfl_coming_soon_plugin_options', 'epfl_coming_soon_plugin_options_validate');
    
    add_settings_section('epfl_coming_soon_plugin_settings', 'Settings', 'epfl_coming_soon_plugin_section_text', 'epfl_coming_soon_plugin');
    add_settings_field('epfl_coming_soon_plugin_options_status', 'Status', 'epfl_coming_soon_plugin_setting_status', 'epfl_coming_soon_plugin', 'epfl_coming_soon_plugin_settings');
    add_settings_field('epfl_coming_soon_plugin_setting_theme_maintenance', 'Use theme maintenance page', 'epfl_coming_soon_plugin_setting_theme_maintenance', 'epfl_coming_soon_plugin', 'epfl_coming_soon_plugin_settings');
    add_settings_field('epfl_coming_soon_plugin_setting_status_code', 'Use 503 status', 'epfl_coming_soon_plugin_setting_status_code', 'epfl_coming_soon_plugin', 'epfl_coming_soon_plugin_settings');
}
add_action('admin_init', 'epfl_coming_soon_register_settings');

function epfl_coming_soon_plugin_section_text()
{
    echo "<p>In this section you can parametrize the EPFL Coming Soon plugin</p>";
}

function _get_coming_soon_status()
{
    $options =  get_option('epfl_coming_soon_plugin_options');
    return $options['status'];
}

function epfl_coming_soon_plugin_setting_status()
{
    $epfl_coming_soon_options = get_option('epfl_coming_soon_plugin_options');
    $epfl_coming_soon_status = $epfl_coming_soon_options['status'] ?? 'off';
    echo "<input id='epfl_coming_soon_plugin_setting_status' name='epfl_coming_soon_plugin_options[status]' type='radio' value='on' ". ($epfl_coming_soon_status === "on" ? "checked='checked'" : "") ." /> ON<br>";
    echo "<input id='epfl_coming_soon_plugin_setting_status' name='epfl_coming_soon_plugin_options[status]' type='radio' value='off' ". ($epfl_coming_soon_status === "off" ? "checked='checked'" : "") ." /> OFF";
}

function epfl_coming_soon_plugin_setting_theme_maintenance()
{
    $epfl_coming_soon_options = get_option('epfl_coming_soon_plugin_options');
    $epfl_coming_soon_theme_maintenance = $epfl_coming_soon_options['theme_maintenance'] ?? 'no';
    echo "<input id='epfl_coming_soon_plugin_setting_theme_maintenance_yes' name='epfl_coming_soon_plugin_options[theme_maintenance]' type='radio' value='yes' ". ($epfl_coming_soon_theme_maintenance === "yes" ? "checked='checked'" : "") ." /> Yes, if present <br>";
    echo "<input id='epfl_coming_soon_plugin_setting_theme_maintenance_no' name='epfl_coming_soon_plugin_options[theme_maintenance]' type='radio' value='no' ". ($epfl_coming_soon_theme_maintenance === "no" ? "checked='checked'" : "") ." /> No, use XXX ";
}

function epfl_coming_soon_plugin_setting_status_code()
{
    $epfl_coming_soon_options = get_option('epfl_coming_soon_plugin_options');
    $epfl_coming_soon_status_code = $epfl_coming_soon_options['status_code'] ?? 'no';
    echo "<input id='epfl_coming_soon_plugin_setting_status_code_503_yes' name='epfl_coming_soon_plugin_options[status_code]' type='radio' value='yes' ". ($epfl_coming_soon_status_code === "yes" ? "checked='checked'" : "") ." /> Yes, use 503 HTTP status code<br>";
    echo "<input id='epfl_coming_soon_plugin_setting_status_code_503_no' name='epfl_coming_soon_plugin_options[status_code]' type='radio' value='no' ". ($epfl_coming_soon_status_code === "no" ? "checked='checked'" : "") ." /> No, just display the page with a 200 HTTP status code ";
}


add_action('plugins_loaded', '_epfl_maintenance_load');
function _epfl_maintenance_load()
{
    // TODO check $epfl_coming_soon_status_code
    if (! is_user_logged_in() && ! is_admin() && _get_coming_soon_status() === 'on') {
        header('HTTP/1.1 503 Service Temporarily Unavailable');
        header('Status: 503 Service Temporarily Unavailable');
        header('Retry-After: 30'); // retry in a day
        die("MAINTENANCE OR SOMETHING");
    }
}


add_action('admin_bar_menu', 'my_new_toolbar_item', 999);

function my_new_toolbar_item($wp_admin_bar)
{
    $args = array(
        'id'    => 'epfl-coming-soon-status',
        'title' => 'EPFL Coming soon is active',
        'href'  => admin_url() . 'options-general.php?page=epfl-coming-soon',
    );
    if (_get_coming_soon_status() === 'on' && ! is_admin()) {
        $wp_admin_bar->add_node($args);
    }
}
