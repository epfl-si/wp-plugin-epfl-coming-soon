<?php
/*
Plugin Name: EPFL Coming Soon
Plugin URI: https://github.com/epfl-si/wp-plugin-epfl-coming-soon
Description: EPFL coming soon / maintenance plugin
Author: EPFL SI
Version: 0.0.2
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
    register_setting('epfl_coming_soon_plugin_options', 'epfl_csp_options', 'epfl_coming_soon_plugin_options_validate');

    add_settings_section('epfl_coming_soon_plugin_settings', 'Settings', 'epfl_coming_soon_plugin_section_text', 'epfl_coming_soon_plugin');
    add_settings_field('epfl_coming_soon_plugin_setting_status', 'Status', 'epfl_coming_soon_plugin_setting_status', 'epfl_coming_soon_plugin', 'epfl_coming_soon_plugin_settings');
    add_settings_field('epfl_coming_soon_plugin_setting_theme_maintenance', 'Use theme maintenance page', 'epfl_coming_soon_plugin_setting_theme_maintenance', 'epfl_coming_soon_plugin', 'epfl_coming_soon_plugin_settings');
    add_settings_field('epfl_coming_soon_plugin_setting_status_code', 'Use 503 status', 'epfl_coming_soon_plugin_setting_status_code', 'epfl_coming_soon_plugin', 'epfl_coming_soon_plugin_settings');

    add_settings_section('epfl_coming_soon_plugin_page_settings', 'Page content', 'epfl_coming_soon_plugin_page_content_section_text', 'epfl_coming_soon_plugin');
    add_settings_field('epfl_coming_soon_plugin_setting_page_title', 'Page title', 'epfl_coming_soon_plugin_page_title', 'epfl_coming_soon_plugin', 'epfl_coming_soon_plugin_page_settings');
    add_settings_field('epfl_coming_soon_plugin_setting_page_content', 'HTML page content', 'epfl_coming_soon_plugin_page_content', 'epfl_coming_soon_plugin', 'epfl_coming_soon_plugin_page_settings');
}
add_action('admin_init', 'epfl_coming_soon_register_settings');

function epfl_coming_soon_plugin_section_text()
{
    echo "<p>In this section you can parametrize the EPFL Coming Soon plugin</p>";
}

function epfl_coming_soon_plugin_page_content_section_text()
{
    echo "<p>In this section you can modify the HTML content of page</p>";
}

function epfl_coming_soon_plugin_page_content()
{
    $epfl_coming_soon_plugin_page_source = get_option('epfl_csp_options')['page_content'] ?? 'Coming soon';
    wp_editor($epfl_coming_soon_plugin_page_source, "epfl_coming_soon_page_source_editor", array('textarea_name' => 'epfl_csp_options[page_content]'));
}

function epfl_coming_soon_plugin_page_title()
{
    $epfl_coming_soon_plugin_page_title = get_option('epfl_csp_options')['page_title'] ?? 'Coming soon';
    echo '<input type="text" value="'. $epfl_coming_soon_plugin_page_title .'" name="epfl_csp_options[page_title]" id="epfl_coming_soon_plugin_page_title" /> <label for="epfl_coming_soon_plugin_page_title">The title of the page (will be prefixed by "' . get_bloginfo('name') . ' &raquo;")</label>' ;
}

function get_plugin_version()
{
    $plugin_data = get_plugin_data(__FILE__);
    return $plugin_data['Version'];
}

function _get_coming_soon_status()
{
    $options =  get_option('epfl_csp_options');
    return $options['status'];
}

function epfl_coming_soon_plugin_setting_status()
{
    $epfl_coming_soon_options = get_option('epfl_csp_options');
    $epfl_coming_soon_status = $epfl_coming_soon_options['status'] ?? 'off';
    echo "<input id='epfl_coming_soon_plugin_setting_status_on' name='epfl_csp_options[status]' type='radio' value='on' ". ($epfl_coming_soon_status === "on" ? "checked='checked'" : "") ." /> <label for='epfl_coming_soon_plugin_setting_status_on'>ON</label><br>";
    echo "<input id='epfl_coming_soon_plugin_setting_status_off' name='epfl_csp_options[status]' type='radio' value='off' ". ($epfl_coming_soon_status === "off" ? "checked='checked'" : "") ." /> <label for='epfl_coming_soon_plugin_setting_status_off'>OFF</label>";
}

function epfl_coming_soon_plugin_setting_theme_maintenance()
{
    $epfl_coming_soon_options = get_option('epfl_csp_options');
    $epfl_coming_soon_theme_maintenance = $epfl_coming_soon_options['theme_maintenance'] ?? 'no';
    echo "<input id='epfl_coming_soon_plugin_setting_theme_maintenance_yes' name='epfl_csp_options[theme_maintenance]' type='radio' value='yes' ". ($epfl_coming_soon_theme_maintenance === "yes" ? "checked='checked'" : "") ." /> <label for='epfl_coming_soon_plugin_setting_theme_maintenance_yes'>Yes, if present (maintenance.php)</label><br>";
    echo "<input id='epfl_coming_soon_plugin_setting_theme_maintenance_no' name='epfl_csp_options[theme_maintenance]' type='radio' value='no' ". ($epfl_coming_soon_theme_maintenance === "no" ? "checked='checked'" : "") ." /> <label for='epfl_coming_soon_plugin_setting_theme_maintenance_no'>No, use the HTML code provided below</label>";
}

function epfl_coming_soon_plugin_setting_status_code()
{
    $epfl_coming_soon_options = get_option('epfl_csp_options');
    $epfl_coming_soon_status_code = $epfl_coming_soon_options['status_code'] ?? 'no';
    echo "<input id='epfl_coming_soon_plugin_setting_status_code_503_yes' name='epfl_csp_options[status_code]' type='radio' value='yes' ". ($epfl_coming_soon_status_code === "yes" ? "checked='checked'" : "") ." /> <label for='epfl_coming_soon_plugin_setting_status_code_503_yes'>Yes, use 503 HTTP status code</label><br>";
    echo "<input id='epfl_coming_soon_plugin_setting_status_code_503_no' name='epfl_csp_options[status_code]' type='radio' value='no' ". ($epfl_coming_soon_status_code === "no" ? "checked='checked'" : "") ." /> <label for='epfl_coming_soon_plugin_setting_status_code_503_no'>No, just display the page with a 200 HTTP status code</label>";
}


add_action('plugins_loaded', '_epfl_maintenance_load');
function _epfl_maintenance_load()
{
    if (! is_user_logged_in() && ! is_admin() && _get_coming_soon_status() === 'on') {
        if (get_option('epfl_coming_soon_plugin_options')['status_code'] === 'yes') {
            header('HTTP/1.1 503 Service Temporarily Unavailable');
            header('Status: 503 Service Temporarily Unavailable');
            header('Retry-After: 43200'); // retry in a ½ day
        }

        if (false /*check theme maintenance page*/) {
            // TODO
        } elseif (trim(get_option('epfl_csp_options')['page_content']) !== '') {
            $epfl_coming_soon_template = file_get_contents(__DIR__ . '/page-template.html');
            $epfl_coming_soon_template = str_replace("{{ TITLE }}", get_bloginfo('name') . ' &raquo; ' . get_option('epfl_csp_options')['page_title'], $epfl_coming_soon_template);
            $epfl_coming_soon_template = str_replace("{{ CONTENT }}", get_option('epfl_csp_options')['page_content'], $epfl_coming_soon_template);
            echo $epfl_coming_soon_template;
            die(/* do nothing more */);
        } else {
            die("Sorry, site's not ready yes.");
        }
    }
}

add_action('admin_bar_menu', 'epfl_coming_soon_admin_bar_entry', 999);

function epfl_coming_soon_admin_bar_entry($wp_admin_bar)
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

function is_plugin_activated($plugin)
{
    $plugin_list = get_option('active_plugins');
    return in_array($plugin, $plugin_list);
}

function comming_soon_api()
{
    if (is_plugin_activated('epfl-coming-soon/epfl-coming-soon.php')) {
        $data = array();
        $data["status"] = get_option('epfl_csp_options')['status'];
        $data["status_code"] = get_option('epfl_csp_options')['status_code'];
        $data["theme_maintenance"] = get_option('epfl_csp_options')['theme_maintenance'];
        $data["version"] = get_plugin_version();
        return new WP_REST_Response($data, 200);
    }
}

add_action('rest_api_init', function () {
    register_rest_route('comingsoon/v1/', '/status', array(
    'methods' => WP_REST_Server::READABLE,
    'callback' => 'comming_soon_api',
  ));
});
