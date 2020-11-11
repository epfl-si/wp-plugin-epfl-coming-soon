<?php

class EPFLComingSoon {

    public function __construct() {
        add_action('admin_menu', array($this, 'epfl_coming_soon_add_settings_page'));
        add_action('admin_init', array($this, 'epfl_coming_soon_register_settings'));
        add_action('admin_bar_menu', array($this, 'epfl_coming_soon_admin_bar_entry'), 999);
        add_action('rest_api_init', array($this, 'epfl_coming_soon_api_rest'));
    }

    private function _is_plugin_activated($plugin)
    {
        $plugin_list = get_option('active_plugins');
        return in_array($plugin, $plugin_list);
    }

    public function epfl_coming_soon_api_rest()
    {
        register_rest_route('comingsoon/v1/', '/status', array(
            'methods' => WP_REST_Server::READABLE,
            'callback' => array($this, 'comming_soon_api'),
        ));
    }

    public function comming_soon_api()
    {
        if ($this->_is_plugin_activated('epfl-coming-soon/epfl-coming-soon.php')) {
            $data = array();
            $data["status"] = $this->_test_maintenance_file() ? 'on' : get_option('epfl_csp_options')['status'];
            $data["status_code"] = get_option('epfl_csp_options')['status_code'];
            $data["theme_maintenance"] = get_option('epfl_csp_options')['theme_maintenance'];
            $data["version"] = $this->get_plugin_version();
            return new WP_REST_Response($data, 200);
        }
    }

    public function get_plugin_version()
    {
        return EPFL_COMING_SOON_VERSION;
        // Note: get_plugin_data(__FILE__) works only when authenticated
    }

    // Found via https://wordpress.stackexchange.com/questions/221202/does-something-like-is-rest-exist
    // → https://wordpress.stackexchange.com/a/356946/130347
    private function _is_rest_api_request()
    {
        if (empty($_SERVER['REQUEST_URI'])) {
            // Probably a CLI request
            return false;
        }

        $rest_prefix         = trailingslashit(rest_get_url_prefix());
        $is_rest_api_request = (false !== strpos($_SERVER['REQUEST_URI'], $rest_prefix));

        return apply_filters('is_rest_api_request', $is_rest_api_request);
    }

    private function _test_maintenance_file()
    {
        $maintenance_file = WP_CONTENT_DIR . '/../.maintenance';
        return file_exists($maintenance_file);
    }

    private function _test_theme_maintenance_file()
    {
        $maintenance_file = get_template_directory() . '/maintenance.php';
        return file_exists($maintenance_file);
    }

    private function _get_coming_soon_status()
    {
        $options =  get_option('epfl_csp_options');
        return $options['status'];
    }

    private function _do_template_replacement($template)
    {
        // Get the stylesheets of the EPFL theme (TODO: find a smarter and automated way...)
        if (strtolower(wp_get_theme()->name) === 'epfl') {
            $style = '<link rel="stylesheet" href="' . get_stylesheet_uri() . '">'."\n";
            $style .= '    <link rel="stylesheet" href="http://wp-httpd/wp-content/themes/wp-theme-2018.git/wp-theme-2018/assets/css/base.css">'."\n";
            $style .= '    <link rel="stylesheet" href="http://wp-httpd/wp-content/themes/wp-theme-2018.git/wp-theme-2018/assets/css/vendor.min.css">'."\n";
        } else {
            $style = '    <link rel="stylesheet" href="'.get_stylesheet_uri().'">';
        }

        $tmplParams = [
            // // Define the HTML <base> element in the template
            // // See https://developer.mozilla.org/en-US/docs/Web/HTML/Element/base
            '{{ BASE_URL }}' => home_url(''),
            '{{ GENERATOR }}' => 'epfl-coming-soon v' . $this->get_plugin_version(),
            '{{ TITLE }}' => get_bloginfo('name') . ' &raquo; ' . get_option('epfl_csp_options')['page_title'],
            '{{ DESCRIPTION }}' => get_bloginfo('description'),
            '{{ STYLE }}' => $style,
            '{{ CONTENT }}' => get_option('epfl_csp_options')['page_content'],
        ];

        return strtr($template, $tmplParams);
    }

    public function epfl_maintenance_load()
    {
        // Leave wp-admin / wp-login apart from epfl-coming-soon plugin
        if (preg_match("/login|admin|dashboard|account/i", $_SERVER['REQUEST_URI']) > 0) {
            return;
        }

        if (php_sapi_name() !== 'cli'                      // not on cli (e.g. wp-cli)
            && ! is_user_logged_in()                       // not when the user is authenticaed
            && ! is_admin()                                // not on back office
            && ! $this->_is_rest_api_request()             // not on rest API routes
            && ( $this->_get_coming_soon_status() === 'on' // only if the plugin is armed
                 || $this->_test_maintenance_file())       // or if the .maintenance file is present
        ) {

            // By default, send HTTP 503 status code along with the content
            if (get_option('epfl_csp_options')['status_code'] === 'yes') {
                status_header(503);
                header('Retry-After: 43200'); // retry in a ½ day
            }

            // In case the user wants to use his theme's maintenance page — need improvements
            if ($this->_test_theme_maintenance_file() && get_option('epfl_csp_options')['theme_maintenance'] === 'yes') {
                // NOTE: I've looked around to find a way to render the current theme
                //       but can't find a way to do it. The ideal solution shall be to
                //       load the maintenance page as the 404.php page, e.g. with the 
                //       theme's header and footer. (see `load_template`)
                //       Another way to achieve that could be to ask the user to set the 
                //       page ID he wants to display as coming soon / maintenance page
                //       and then use:
                //         $the_page_link = get_permalink( $the_page_id );
                //         wp_redirect( $the_page_link );
                //       But right now, maintenance.php have to be self contained..!
                include_once(get_template_directory() . '/maintenance.php');
                exit();

            // Whenever the user create the page in the plugin's TinyMCE editor
            } elseif (trim(get_option('epfl_csp_options')['page_content']) !== '') {
                $template_path = __DIR__ . '/../templates/page-template.html';
                $epfl_coming_soon_template = file_get_contents($template_path);

                // Display the template
                echo $this->_do_template_replacement($epfl_coming_soon_template);

                exit();

            // In every other cases, just display a plain text sorry message
            } else {
                die("Sorry, site's not ready yet.");
            }
        }
    }

    public function epfl_coming_soon_add_settings_page()
    {
        add_options_page('EPFL Coming Soon', 'EPFL Coming Soon', 'manage_options', 'epfl-coming-soon', array($this, 'epfl_coming_soon_render_plugin_settings_page'));
    }

    public function epfl_coming_soon_render_plugin_settings_page()
    {
        ?>
        <h2>EPFL Coming Soon</h2>
        <form action="options.php" method="post">
            <?php
            settings_fields('epfl_coming_soon_plugin_options');
            do_settings_sections('epfl_coming_soon_plugin'); ?>
            <input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e('Save'); ?>" />
        </form>
        <?php
    }

    // https://deliciousbrains.com/create-wordpress-plugin-settings-page/
    public function epfl_coming_soon_register_settings()
    {
        register_setting('epfl_coming_soon_plugin_options', 'epfl_csp_options', 'epfl_coming_soon_plugin_options_validate');

        add_settings_section('epfl_coming_soon_plugin_settings', 'Settings', array($this, 'epfl_coming_soon_plugin_section_text'), 'epfl_coming_soon_plugin');
        add_settings_field('epfl_coming_soon_plugin_setting_status', 'Status', array($this, 'epfl_coming_soon_plugin_setting_status'), 'epfl_coming_soon_plugin', 'epfl_coming_soon_plugin_settings');
        add_settings_field('epfl_coming_soon_plugin_setting_theme_maintenance', 'Use theme maintenance page', array($this, 'epfl_coming_soon_plugin_setting_theme_maintenance'), 'epfl_coming_soon_plugin', 'epfl_coming_soon_plugin_settings');
        add_settings_field('epfl_coming_soon_plugin_setting_status_code', 'Use 503 status', array($this, 'epfl_coming_soon_plugin_setting_status_code'), 'epfl_coming_soon_plugin', 'epfl_coming_soon_plugin_settings');

        add_settings_section('epfl_coming_soon_plugin_page_settings', 'Displayed page', array($this, 'epfl_coming_soon_plugin_page_content_section_text'), 'epfl_coming_soon_plugin');
        add_settings_field('epfl_coming_soon_plugin_setting_page_title', 'Page title', array($this, 'epfl_coming_soon_plugin_page_title'), 'epfl_coming_soon_plugin', 'epfl_coming_soon_plugin_page_settings');
        add_settings_field('epfl_coming_soon_plugin_setting_page_content', 'Page content', array($this, 'epfl_coming_soon_plugin_page_content'), 'epfl_coming_soon_plugin', 'epfl_coming_soon_plugin_page_settings');
    }

    public function epfl_coming_soon_plugin_section_text()
    {
        echo "<p>In this section you can parametrize the EPFL Coming Soon plugin</p>";
    }

    public function epfl_coming_soon_plugin_page_content_section_text()
    {
        echo "<p>In this section you can modify the coming soon / maintenance page diplayed</p>";
    }

    public function epfl_coming_soon_plugin_page_content()
    {
        $default_page_content = <<<EOD
        &nbsp;

        &nbsp;
        <p style="text-align: center;"><img class="img-fluid aligncenter" src="https://web2018.epfl.ch/5.0.2/icons/epfl-logo.svg" alt="Logo EPFL" width="388" height="113" /></p>

        <h3 style="text-align: center; color: #ff0000; font-family: Helvetica, Arial, sans-serif;">Something new is coming...</h3>
        <p style="position: absolute; bottom: 0; left: 0; width: 100%; text-align: center;"><a href="wp-admin/">Connexion / Login</a></p>
EOD;
        $epfl_coming_soon_plugin_page_source = get_option('epfl_csp_options')['page_content'] ?? $default_page_content;
        wp_editor($epfl_coming_soon_plugin_page_source, "epfl_coming_soon_page_source_editor", array('textarea_name' => 'epfl_csp_options[page_content]'));
    }

    public function epfl_coming_soon_plugin_page_title()
    {
        $epfl_coming_soon_plugin_page_title = get_option('epfl_csp_options')['page_title'] ?? 'Coming soon';
        echo '<input type="text" value="'. $epfl_coming_soon_plugin_page_title .'" name="epfl_csp_options[page_title]" id="epfl_coming_soon_plugin_page_title" />' ;
        echo '<p class="description" id="epfl_coming_soon_plugin_page_title-description"> <label for="epfl_coming_soon_plugin_page_title">The title of the page (will be prefixed by site title, i.e. "' . get_bloginfo('name') . ' &raquo;")</label></p>';
    }

    public function epfl_coming_soon_plugin_setting_status()
    {
        $epfl_coming_soon_options = get_option('epfl_csp_options');
        $epfl_coming_soon_status = $epfl_coming_soon_options['status'] ?? 'off';
        echo "<input id='epfl_coming_soon_plugin_setting_status_on' name='epfl_csp_options[status]' type='radio' value='on' ". ($epfl_coming_soon_status === "on" ? "checked='checked'" : "") ." /> <label for='epfl_coming_soon_plugin_setting_status_on'>ON</label><br>";
        echo "<input id='epfl_coming_soon_plugin_setting_status_off' name='epfl_csp_options[status]' type='radio' value='off' ". ($epfl_coming_soon_status === "off" ? "checked='checked'" : "") ." /> <label for='epfl_coming_soon_plugin_setting_status_off'>OFF</label>";
    }

    public function epfl_coming_soon_plugin_setting_theme_maintenance()
    {
        $epfl_coming_soon_options = get_option('epfl_csp_options');
        $epfl_coming_soon_theme_maintenance = $epfl_coming_soon_options['theme_maintenance'] ?? 'no';
        echo "<input id='epfl_coming_soon_plugin_setting_theme_maintenance_yes' name='epfl_csp_options[theme_maintenance]' type='radio' value='yes' ". ($epfl_coming_soon_theme_maintenance === "yes" ? "checked='checked'" : "") ." /> <label for='epfl_coming_soon_plugin_setting_theme_maintenance_yes'>Yes, if present (maintenance.php)</label><br>";
        echo "<input id='epfl_coming_soon_plugin_setting_theme_maintenance_no' name='epfl_csp_options[theme_maintenance]' type='radio' value='no' ". ($epfl_coming_soon_theme_maintenance === "no" ? "checked='checked'" : "") ." /> <label for='epfl_coming_soon_plugin_setting_theme_maintenance_no'>No, use the HTML code provided below</label>";
    }

    public function epfl_coming_soon_plugin_setting_status_code()
    {
        $epfl_coming_soon_options = get_option('epfl_csp_options');
        $epfl_coming_soon_status_code = $epfl_coming_soon_options['status_code'] ?? 'yes';
        echo "<input id='epfl_coming_soon_plugin_setting_status_code_503_yes' name='epfl_csp_options[status_code]' type='radio' value='yes' ". ($epfl_coming_soon_status_code === "yes" ? "checked='checked'" : "") ." /> <label for='epfl_coming_soon_plugin_setting_status_code_503_yes'>Yes, use 503 HTTP status code</label><br>";
        echo "<input id='epfl_coming_soon_plugin_setting_status_code_503_no' name='epfl_csp_options[status_code]' type='radio' value='no' ". ($epfl_coming_soon_status_code === "no" ? "checked='checked'" : "") ." /> <label for='epfl_coming_soon_plugin_setting_status_code_503_no'>No, just display the page with a 200 HTTP status code</label>";
    }

    public function epfl_coming_soon_admin_bar_entry($wp_admin_bar)
    {
        $args = array(
            'id'    => 'epfl-coming-soon-status',
            'title' => 'EPFL Coming soon is active',
            'href'  => admin_url() . 'options-general.php?page=epfl-coming-soon',
        );
        if ($this->_get_coming_soon_status() === 'on') {
            $wp_admin_bar->add_node($args);
        }
    }
}
