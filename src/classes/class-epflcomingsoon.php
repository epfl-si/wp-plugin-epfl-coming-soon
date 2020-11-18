<?php
/**
 * File: src/classes/class-epflcoming-soon.php
 *
 * @file
 * @category File
 * @package  epfl-coming-soon
 */

/**
 * Main EPFL Coming Soon Class
 *
 * @category Class
 * @package  epfl-coming-soon
 */
class EPFLComingSoon {
	/**
	 * Constructor
	 **/
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'epfl_coming_soon_add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'epfl_coming_soon_register_settings' ) );
		add_action( 'admin_bar_menu', array( $this, 'epfl_coming_soon_admin_bar_entry' ), 999 );
		add_action( 'rest_api_init', array( $this, 'epfl_coming_soon_rest_api' ) );
	}

	/**
	 * Function is_plugin_activated
	 *
	 * @param String $plugin The name of the plugin.
	 **/
	private function is_plugin_activated( $plugin ) {
		$plugin_list = get_option( 'active_plugins' );
		return in_array( $plugin, $plugin_list, true );
	}

	/**
	 * EPFL coming soon rest API
	 **/
	public function epfl_coming_soon_rest_api() {
		register_rest_route(
			'epfl/v1/',
			'coming-soon',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'comming_soon_api' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Comming soon API
	 **/
	public function comming_soon_api() {
		if ( $this->is_plugin_activated( 'epfl-coming-soon/epfl-coming-soon.php' ) ) {
			$epfl_coming_soon_status_code = $epfl_coming_soon_options['status_code'] ?? '200';
			// Transform yes/no values in 503/200 values for more readibility. Retro-compatible.
			if ( 'no' === $epfl_coming_soon_status_code ) {
				$epfl_coming_soon_status_code = '200';
			} elseif ( 'yes' === $epfl_coming_soon_status_code ) {
				$epfl_coming_soon_status_code = '503';
			}
			$data                      = array();
			$data['status']            = $this->test_maintenance_file() ? '1' : ( 'on' === get_option( 'epfl_csp_options' )['status'] ? '1' : '0' );
			$data['maintenance_mode']  = $this->test_maintenance_file() ? '1' : '0';
			$data['status_code']       = $epfl_coming_soon_status_code;
			$data['theme_maintenance'] = 'on' === get_option( 'epfl_csp_options' )['theme_maintenance'] ? '1' : '0';
			$data['version']           = $this->get_plugin_version();
			return new WP_REST_Response( $data, 200 );
		}
	}

	/**
	 * Get plugin version
	 */
	public function get_plugin_version() {
		return EPFL_COMING_SOON_VERSION;
		// Note: get_plugin_data(__FILE__) works only when authenticated.
	}

	/**
	 * Found via https://wordpress.stackexchange.com/questions/221202/does-something-like-is-rest-exist
	 * See https://wordpress.stackexchange.com/a/356946/130347.
	 */
	private function is_rest_api_request() {
		if ( empty( $_SERVER['REQUEST_URI'] ) ) {
			// This is probably a CLI request...
			return false;
		}

		$rest_prefix         = trailingslashit( rest_get_url_prefix() );
		$is_rest_api_request = ( false !== strpos( $_SERVER['REQUEST_URI'], $rest_prefix ) );

		return apply_filters( 'is_rest_api_request', $is_rest_api_request );
	}

	/**
	 * Test maintenance file
	 */
	private function test_maintenance_file() {
		$maintenance_file = WP_CONTENT_DIR . '/../.maintenance';
		return file_exists( $maintenance_file );
	}

	/**
	 * Test theme maintenance file
	 */
	private function test_theme_maintenance_file() {
		$maintenance_file = get_template_directory() . '/maintenance.php';
		return file_exists( $maintenance_file );
	}

	/**
	 * Get coming soon status
	 */
	private function get_coming_soon_status() {
		$options = get_option( 'epfl_csp_options' );
		return $options['status'];
	}

	/**
	 * Do template replacement

	 * @param String $template File content with variable to replace.
	 */
	private function do_template_replacement( $template ) {
		// Get the stylesheets of the EPFL theme (TODO: find a smarter and automated way...).
		if ( strtolower( wp_get_theme()->name ) === 'epfl' ) {
			$style  = "<link rel='stylesheet' href='" . get_stylesheet_uri() . "'>\n";
			$style .= "    <link rel='stylesheet' href='" . get_stylesheet_directory_uri() . '/assets/css/base.css' . "'>\n";
		} else {
			$style = '    <link rel="stylesheet" href="' . get_stylesheet_uri() . '">';
		}

		$template_params = array(
			// Define the HTML <base> element in the template
			// See https://developer.mozilla.org/en-US/docs/Web/HTML/Element/base.
			'{{ BASE_URL }}'    => home_url( '/' ),
			'{{ GENERATOR }}'   => 'epfl-coming-soon v' . $this->get_plugin_version(),
			'{{ TITLE }}'       => get_bloginfo( 'name' ) . ' &raquo; ' . get_option( 'epfl_csp_options' )['page_title'],
			'{{ DESCRIPTION }}' => get_bloginfo( 'description' ),
			'{{ STYLE }}'       => $style,
			'{{ CONTENT }}'     => get_option( 'epfl_csp_options' )['page_content'],
		);

		return strtr( $template, $template_params );
	}

	/**
	 * EPFL maintenance load
	 */
	public function epfl_maintenance_load() {
		// Leave wp-admin / wp-login apart from epfl-coming-soon plugin.
		if ( preg_match( '/login|admin|dashboard|account/i', $_SERVER['REQUEST_URI'] ) > 0 ) {
			return;
		}

		if ( php_sapi_name() !== 'cli'                    // not on cli (e.g. wp-cli).
			&& ! is_user_logged_in()                      // not when the user is authenticaed.
			&& ! is_admin()                               // not on back office.
			&& ! $this->is_rest_api_request()             // not on rest API routes.
			&& ( $this->get_coming_soon_status() === 'on' // only if the plugin is armed.
				|| $this->test_maintenance_file() )       // or if the .maintenance file is present.
		) {

			// By default, send HTTP 503 status code along with the content.
			if ( 'yes' === get_option( 'epfl_csp_options' )['status_code'] ) {
				status_header( 503 );
				header( 'Retry-After: 43200' ); // retry in a ½ day.
			}

			// In case the user wants to use his theme's maintenance page — need improvements.
			if ( 'yes' === $this->test_theme_maintenance_file() && get_option( 'epfl_csp_options' )['theme_maintenance'] ) {
				// NOTE: I've looked around to find a way to render the current theme
				// but can't find a way to do it. The ideal solution shall be to
				// load the maintenance page as the 404.php page, e.g. with the
				// theme's header and footer. (see `load_template`)
				// Another way to achieve that could be to ask the user to set the
				// page ID he wants to display as coming soon / maintenance page
				// and then use:
				// $the_page_link = get_permalink( $the_page_id );
				// wp_redirect( $the_page_link );
				// But right now, maintenance.php have to be self contained..!
				include_once get_template_directory() . '/maintenance.php';
				exit();

				// Whenever the user create the page in the plugin's TinyMCE editor.
			} elseif ( trim( get_option( 'epfl_csp_options' )['page_content'] ) !== '' ) {
				$template_path             = __DIR__ . '/../templates/page-template.html';
				$epfl_coming_soon_template = file_get_contents( $template_path );

				// Display the template.
				echo $this->do_template_replacement( $epfl_coming_soon_template );

				exit();

				// In every other cases, just display a plain text sorry message.
			} else {
				die( "Sorry, site's not ready yet." );
			}
		}
	}

	/**
	 * EPFL coming soon add settings page
	 */
	public function epfl_coming_soon_add_settings_page() {
		add_options_page( 'EPFL Coming Soon', 'EPFL Coming Soon', 'manage_options', 'epfl-coming-soon', array( $this, 'epfl_coming_soon_render_plugin_settings_page' ) );
	}

	/**
	 * EPFL coming soon render plugin settings page
	 */
	public function epfl_coming_soon_render_plugin_settings_page() {
		?>
		<h2>EPFL Coming Soon</h2>
		<div class="notice notice-info">
			<p>
				<?php _e( 'EPFL Coming Soon is a plugin that allows you to display a page that blocks your website to the vistors. It can be used for displaying a maintenance / under construction / coming soon page / etc.', 'epfl-coming-soon' ); ?><br>
				<?php _e( 'Please head to <a href="https://github.com/epfl-si/wp-plugin-epfl-coming-soon/issues/new" target="_blank">plugin repository</a> for questions, remarks and issues.', 'epfl-coming-soon' ); ?><br>
				<?php _e( 'More information and plugin sources can be reached on <a href="https://github.com/epfl-si/wp-plugin-epfl-coming-soon" target="_blank">GitHub</a>.', 'epfl-coming-soon' ); ?><br>
			</p>
		</div>
		<form action="options.php" method="post">
			<?php
			settings_fields( 'epfl_coming_soon_plugin_options' );
			do_settings_sections( 'epfl_coming_soon_plugin' );
			?>
			<input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e( 'Save' ); ?>" />
		</form>
		<?php
	}

	/**
	 * EPFL coming soon register settings
	 *
	 * Source: https://deliciousbrains.com/create-wordpress-plugin-settings-page/
	 */
	public function epfl_coming_soon_register_settings() {
		register_setting( 'epfl_coming_soon_plugin_options', 'epfl_csp_options', 'epfl_coming_soon_plugin_options_validate' );

		add_settings_section( 'epfl_coming_soon_plugin_settings', __( 'Settings', 'epfl-coming-soon' ), array( $this, 'epfl_coming_soon_plugin_section_text' ), 'epfl_coming_soon_plugin' );
		add_settings_field( 'epfl_coming_soon_plugin_setting_status', __( 'Status', 'epfl-coming-soon' ), array( $this, 'epfl_coming_soon_plugin_setting_status' ), 'epfl_coming_soon_plugin', 'epfl_coming_soon_plugin_settings' );
		add_settings_field( 'epfl_coming_soon_plugin_setting_theme_maintenance', __( 'Use theme\'s page', 'epfl-coming-soon' ), array( $this, 'epfl_coming_soon_plugin_setting_theme_maintenance' ), 'epfl_coming_soon_plugin', 'epfl_coming_soon_plugin_settings' );
		add_settings_field( 'epfl_coming_soon_plugin_setting_status_code', __( 'HTTP status code', 'epfl-coming-soon' ), array( $this, 'epfl_coming_soon_plugin_setting_status_code' ), 'epfl_coming_soon_plugin', 'epfl_coming_soon_plugin_settings' );

		add_settings_section( 'epfl_coming_soon_plugin_page_settings', __( 'Displayed page', 'epfl-coming-soon' ), array( $this, 'epfl_coming_soon_plugin_page_content_section_text' ), 'epfl_coming_soon_plugin' );
		add_settings_field( 'epfl_coming_soon_plugin_setting_page_title', __( 'Page title', 'epfl-coming-soon' ), array( $this, 'epfl_coming_soon_plugin_page_title' ), 'epfl_coming_soon_plugin', 'epfl_coming_soon_plugin_page_settings' );
		add_settings_field( 'epfl_coming_soon_plugin_setting_page_content', __( 'Page content', 'epfl-coming-soon' ), array( $this, 'epfl_coming_soon_plugin_page_content' ), 'epfl_coming_soon_plugin', 'epfl_coming_soon_plugin_page_settings' );
	}

	/**
	 * EPFL coming soon plugin section text
	 */
	public function epfl_coming_soon_plugin_section_text() {
		printf( '<p>%s</p>', esc_html_e( 'In this section you can parametrize the EPFL Coming Soon plugin.', 'epfl-coming-soon' ) );
	}

	/**
	 * EPFL coming soon plugin page content section text
	 */
	public function epfl_coming_soon_plugin_page_content_section_text() {
		printf( '<p>%s</p>', esc_html_e( 'In this section you can modify the coming soon / maintenance page diplayed.', 'epfl-coming-soon' ) );
	}

	/**
	 * EPFL coming soon plugin page content
	 */
	public function epfl_coming_soon_plugin_page_content() {
		$default_page_content                = <<<EOD
        &nbsp;

        &nbsp;
        <p style="text-align: center;"><img class="img-fluid aligncenter" src="https://web2018.epfl.ch/5.0.2/icons/epfl-logo.svg" alt="Logo EPFL" width="388" height="113" /></p>

        <h3 style="text-align: center; color: #ff0000; font-family: Helvetica, Arial, sans-serif;">Something new is coming...</h3>
        <p style="position: absolute; bottom: 0; left: 0; width: 100%; text-align: center;"><a href="wp-admin/">Connexion / Login</a></p>
EOD;
		$epfl_coming_soon_plugin_page_source = get_option( 'epfl_csp_options' )['page_content'] ?? $default_page_content;
		wp_editor( $epfl_coming_soon_plugin_page_source, 'epfl_coming_soon_page_source_editor', array( 'textarea_name' => 'epfl_csp_options[page_content]' ) );
	}

	/**
	 * EPFL coming soon plugin page title
	 */
	public function epfl_coming_soon_plugin_page_title() {
		$epfl_coming_soon_plugin_page_title = get_option( 'epfl_csp_options' )['page_title'] ?? 'Coming soon';
		echo '<input type="text" value="' . $epfl_coming_soon_plugin_page_title . '" name="epfl_csp_options[page_title]" id="epfl_coming_soon_plugin_page_title" />';
		echo '<p class="description" id="epfl_coming_soon_plugin_page_title-description"> <label for="epfl_coming_soon_plugin_page_title">';
		printf(
			/* translators: %s: blog name */
			esc_html__( 'The title of the page (will be prefixed by site title, i.e. "%s &raquo;")', 'epfl-coming-soon' ),
			get_bloginfo( 'name' )
		);
		echo '</label></p>';
	}

	/**
	 * EPFL coming soon plugin setting status
	 */
	public function epfl_coming_soon_plugin_setting_status() {
		$epfl_coming_soon_options = get_option( 'epfl_csp_options' );
		$epfl_coming_soon_status  = $epfl_coming_soon_options['status'] ?? 'off';
		printf( "<input id='epfl_coming_soon_plugin_setting_status_on' name='epfl_csp_options[status]' type='radio' value='on' " . ( 'on' === $epfl_coming_soon_status ? "checked='checked'" : '' ) . " /> <label for='epfl_coming_soon_plugin_setting_status_on'>%s</label><br>", __( 'ON', 'epfl-coming-soon' ) );
		printf( "<input id='epfl_coming_soon_plugin_setting_status_off' name='epfl_csp_options[status]' type='radio' value='off' " . ( 'off' === $epfl_coming_soon_status ? "checked='checked'" : '' ) . " /> <label for='epfl_coming_soon_plugin_setting_status_off'>%s</label>", __( 'OFF', 'epfl-coming-soon' ) );

		// Display a warning if the .maintenance file is present.
		if ( $this->test_maintenance_file() ) {
			printf(
				/* translators: 1: info 2: meaning 3: details */
				'<br><div class="update-nag notice notice-error">%1$s<br>%2$s<br>%3$s</div>',
				__( 'The plugin status have been surcharged by the <code>.maintenance</code> file!', 'epfl-coming-soon' ),
				__( 'This means that the plugin is <b>ACTIVATED</b>, whatever the status is.', 'epfl-coming-soon' ),
				__( 'Remove the file to use normal settings. See <a href="https://developer.wordpress.org/cli/commands/maintenance-mode/" target="_blank">wp-cli maintenance mode</a> for details.', 'epfl-coming-soon' )
			);
		}
	}

	/**
	 * EPFL coming soon plugin setting theme maintenance
	 */
	public function epfl_coming_soon_plugin_setting_theme_maintenance() {
		$epfl_coming_soon_options           = get_option( 'epfl_csp_options' );
		$epfl_coming_soon_theme_maintenance = $epfl_coming_soon_options['theme_maintenance'] ?? 'no';
		printf( "<input id='epfl_coming_soon_plugin_setting_theme_maintenance_no' name='epfl_csp_options[theme_maintenance]' type='radio' value='no' " . ( 'no' === $epfl_coming_soon_theme_maintenance ? "checked='checked'" : '' ) . " /> <label for='epfl_coming_soon_plugin_setting_theme_maintenance_no'>%s</label><br>", __( 'Use the HTML code provided below', 'epfl-coming-soon' ) );
		printf( "<input id='epfl_coming_soon_plugin_setting_theme_maintenance_yes' name='epfl_csp_options[theme_maintenance]' type='radio' value='yes' " . ( 'yes' === $epfl_coming_soon_theme_maintenance ? "checked='checked'" : '' ) . " /> <label for='epfl_coming_soon_plugin_setting_theme_maintenance_yes'>%s</label>", __( 'Use the theme\'s maintenance.php (if present)', 'epfl-coming-soon' ) );
	}

	/**
	 * EPFL coming soon plugin setting status code
	 */
	public function epfl_coming_soon_plugin_setting_status_code() {
		$epfl_coming_soon_options     = get_option( 'epfl_csp_options' );
		$epfl_coming_soon_status_code = $epfl_coming_soon_options['status_code'] ?? '200';
		// Transform yes/no values in 503/200 values for more readibility. Retro-compatible.
		if ( 'no' === $epfl_coming_soon_status_code ) {
			$epfl_coming_soon_status_code = '200';
		} elseif ( 'yes' === $epfl_coming_soon_status_code ) {
			$epfl_coming_soon_status_code = '503';
		}
		printf( "<input id='epfl_coming_soon_plugin_setting_status_code_503_no' name='epfl_csp_options[status_code]' type='radio' value='200' " . ( '200' === $epfl_coming_soon_status_code ? "checked='checked'" : '' ) . " /> <label for='epfl_coming_soon_plugin_setting_status_code_503_no'>%s</label><br>", __( 'Just display the page with a HTTP status code 200 (best for coming soon)', 'epfl-coming-soon' ) );
		printf( "<input id='epfl_coming_soon_plugin_setting_status_code_503_yes' name='epfl_csp_options[status_code]' type='radio' value='503' " . ( '503' === $epfl_coming_soon_status_code ? "checked='checked'" : '' ) . " /> <label for='epfl_coming_soon_plugin_setting_status_code_503_yes'>%s</label>", __( 'Use HTTP status code 503 (best for maintenance)', 'epfl-coming-soon' ) );
	}

	/**
	 * EPFL coming soon admin bar entry

	 * @param WP_Admin_Bar $wp_admin_bar The admin bar.
	 */
	public function epfl_coming_soon_admin_bar_entry( $wp_admin_bar ) {
		$args = array(
			'id'    => 'epfl-coming-soon-status',
			'title' => __( 'EPFL Coming Soon is active', 'epfl-coming-soon' ),
			'href'  => admin_url() . 'options-general.php?page=epfl-coming-soon',
		);
		if ( $this->get_coming_soon_status() === 'on' || $this->test_maintenance_file() ) {
			$wp_admin_bar->add_node( $args );
		}
	}
}
