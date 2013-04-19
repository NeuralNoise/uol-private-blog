<?php
/*
Plugin Name: Privacy plugin
Plugin URI: http://essl-pvac.github.com/plugins/uol-privacy-plugin
Description: This plugin restricts access to a blog so you either have to be logged in or you computer has an IP address in a (configurable) range
Version: 1.0
Author: Peter Edwards <p.l.edwards@leeds.ac.uk>
*/
if (!class_exists("uol_privacy")) :
/**
 * class to redirect users to the wordpress login page if they are either not logged in or 
 * not on a give IP network
 */
class uol_privacy
{
	/**
	 * registers the plugin with the Wordpress API
	 */
	public static function register()
	{
		if (is_multisite()) {
			/* add menu to Network admin */
			add_action('network_admin_menu', array(__CLASS__, 'add_network_admin_menu'));
			/* add menu to blog admin */
			add_action('admin_menu', array(__CLASS__, 'add_blog_admin_menu'));
			/* register plugin admin options */
			add_action( 'admin_init', array(__CLASS__, 'register_plugin_options') );
			/* permissions check */
			add_action( 'wp', array(__CLASS__, 'force_member_login_init') );
			/* i18n */
			add_action('plugins_loaded', array(__CLASS__, 'load_text_domain'));
			/* Use the admin_print_scripts action to add scripts for blog admin page */
			add_action( 'admin_print_scripts', array(__CLASS__, 'admin_scripts') );
		}		
	}

	/**
	 * Internationalization
	 */
	public static function load_text_domain()
	{
		load_plugin_textdomain( 'uol-privacy', false, dirname(plugin_basename(__FILE__)) . '/lang/');
	}

	/**
	 * function which forces user to log in to see the site, or restricts
	 * access by IP address, according to blog and network settings
	 */
	function force_member_login_init() 
	{
		$blog_options = self::get_blog_options();

		if ($blog_options["logged_in_users_only"]) {

			/* If the user is logged in, then abort */
			if ( current_user_can('read') ) return;

			if ($blog_options["allow_network_users"]) {

				$network_options = self::get_network_options();
				
				/* if the user appears to have a valid IP address, abort */
				if ( !empty($network_options) ) {
					$ips = explode("|", $network_options);
					$ip_is_allowed = false;
					foreach ($ips as $ip) {
						if (strpos($_SERVER["REMOTE_ADDR"], $ip) === 0) {
							$ip_is_allowed = true;
						}
					}
					if ($ip_is_allowed) return;
				}
			}

			/* This is an array of pages that will be EXCLUDED from being blocked */
			$exclusions = array(
				'wp-login.php',
				'wp-cron.php', // Just incase
				'wp-trackback.php',
				'xmlrpc.php'
			);

			/* If the current script name is in the exclusion list, abort */
			if ( in_array( basename($_SERVER['PHP_SELF']), $exclusions) ) return;

			/* Still here? Okay, then redirect to the login form */
			auth_redirect();
		}
	}

	/**
	 * uses add_submenu_page to add a menu item to the Network Settings menu
	 */
	public static function add_network_admin_menu()
	{
		/* adjust capability according to activation status of plugin */
		add_submenu_page( 'settings.php', __('Privacy Settings', 'uol-privacy'), __('Privacy Settings', 'uol-privacy'), 'manage_network_options', 'uol-privacy', array(__CLASS__, 'get_network_admin_page'));
	}

	/**
	 * uses add_submenu_page to add a menu item to the Settings menu
	 */
	public static function add_blog_admin_menu()
	{
		/* adjust capability according to activation status of plugin */
		add_submenu_page( 'options-general.php', __('Privacy Settings', 'uol-privacy'), __('Privacy Settings', 'uol-privacy'), 'manage_options', 'uol-privacy-options', array(__CLASS__, 'get_blog_admin_page'));
	}

	/**
	 * options page
	 */
	public static function get_network_admin_page()
	{
		/* check that the user has the required capability */ 
		if (!current_user_can('manage_network_options'))
		{
			wp_die( __('You do not have sufficient permissions to access this page.', 'uol-privacy') );
		}

		$ips = self::get_network_options();

		/* save options */
		if (isset($_POST["ip_allow_list"]) && wp_verify_nonce( $_POST['ip_allow_list'], 'ip_allow_list_nonce' )) {
			if (trim($_POST["ip_list"]) != '') {
				$potential_ips = array_unique(array_map('trim', explode("\n", $_POST["ip_list"])));
				$valid_ips = array();
				foreach ($potential_ips as $ip) {
					$isValid = true;
					/* make sure it is only numbers and dots, then trim */
					$ip = trim(preg_replace('/[^0-9\.]*/', '', $ip), ". \t\n\r\0\x0B");
					/* split into quads */
					$quads = explode(".", $ip);
					/* test each quad */
					foreach ($quads as $quad) {
						if (!preg_match('/^[0-9]{1,3}$/', $quad)) {
							$isValid = false;
						}
					}
					/* only allow four */
					if (count($quads) > 4) {
						$ip = implode(".", array_slice($quads, 0, 4));
					}
					if ($isValid) {
						$valid_ips[] = $ip;
					}
				}
				if (count($valid_ips)) {
					$new_ips = implode("|", $valid_ips);
				} else {
					$new_ips = '';
				}
			} else {
				$new_ips = '';
			}
			update_site_option('uol-privacy-network', $new_ips);
			$ips = $new_ips;
		}
		printf('<div class="wrap"><div id="icon-options-general" class="icon32"></div><h2>%s</h2><form method="post" action="">', __('Privacy settings', 'uol-privacy'));
		printf('<input type="hidden" name="ip_allow_list" id="ip_allow_list" value="%s" />', wp_create_nonce('ip_allow_list_nonce'));
		printf('<p>%s</p><p><textarea name="ip_list" id="ip_list" cols="60" rows="20">%s</textarea></p>', __('Input a list of IP addresses, separated by linebreaks, to allow onto the site without the user having to log in. Partial IP addresses are OK', 'uol-privacy'), str_replace("|", "\n", $ips));
		printf('<p><input type="submit" name="submit" class="button-primary" value="%s" /></p></form></div>', __('Save Changes', 'uol-privacy'));
	}

	/**
	 * gets the admin page for individual blogs
	 */
	public static function get_blog_admin_page()
	{
		/* check that the user has the required capability */ 
		if (!current_user_can('manage_options')) {
			wp_die( __('You do not have sufficient permissions to access this page.', 'uol-privacy') );
		}
		printf('<div class="wrap"><div id="icon-options-general" class="icon32"></div><h2>%s</h2><form method="post" action="options.php">', __('Privacy settings', 'uol-privacy'));
		settings_errors('uol_privacy_options');
		print('<form method="post" action="options.php">');
		settings_fields('uol_privacy_options');
		do_settings_sections('uol-privacy-options');
		printf('<p><input type="submit" class="button-primary" name="submit" value="%s" /></p>', __('Save Changes', 'uol-privacy'));
		print('</form>');
		printf("<script>var uol_allowed_ips='%s';</script>", self::get_network_options());
		print('</div>');
	}

	/**
	 * registers settings and sections
	 */
	function register_plugin_options()
	{
		/* register a setting */
		register_setting(
			'uol_privacy_options',
			'uol_privacy_options',
			array(__CLASS__, 'validate_uol_privacy_options')
		);
				
		/* configure options for the setting */
		add_settings_section(
			'uol-privacy',
			__('Control who has access to this site', 'uol-privacy'),
			array(__CLASS__, 'section_text'),
			'uol-privacy-options'
		);

		add_settings_field(
			'logged_in_users_only',
			__('Require login', 'uol-privacy'),
			array(__CLASS__, 'setting_checkbox'),
			'uol-privacy-options',
			'uol-privacy',
			array(
				"fieldname" => "logged_in_users_only", 
				"description" => __('Check this box to require users to log in to see this site', 'event-post-type')
			)
		);

		$args = array(
			"fieldname" => 'allow_network_users',
			"description" => __('Check this box to allow users on the network to see this site without logging in', 'uol-privacy'),
		);
		if (current_user_can('manage_network_options')) {
			$args["description"] .= sprintf('<br /><a href="%s">%s</a>', network_admin_url('settings.php?page=uol-privacy'), __('Click here to configure IP addresses allowed on the network', 'uol-privacy'));
		}
		add_settings_field(
			'allow_network_users',
			__('Allow network', 'uol-privacy'),
			array(__CLASS__, 'setting_checkbox'),
			'uol-privacy-options',
			'uol-privacy',
			$args
		);
	}

	/**
	 * settings section text
	 */
	public static function section_text()
		{ echo ""; }


	/**
	 * input field for checkbox
	 */
	public static function setting_checkbox($args)
	{
		$field = $args["fieldname"];
		$options = self::get_blog_options();
		$chckd = ($options[$field])? ' checked="checked"': '';
		printf('<input id="uol_privacy_options-%s" name="uol_privacy_options[%s]" type="checkbox"%s />', $field, $field, $chckd);
		if (isset($args["description"]) && $args["description"] != "") {
			print("<p><em>" . $args["description"] . "</em></p>");
		}
	}

	/**
	 * validates plugin settings
	 */
	public static function validate_uol_privacy_options($new_options)
	{
		$options["logged_in_users_only"] = isset($new_options["logged_in_users_only"]);
		$options["allow_network_users"] = isset($new_options["allow_network_users"]);
		if (!$options["logged_in_users_only"]) {
			$options["allow_network_users"] = false;
		}
		return $options;
	}

	/**
	 * gets plugin network option value (a list of IP addresses), or returns the empty string
	 */
	public static function get_network_options() 
	{
		$option = get_site_option('uol-privacy-network');
		if ($option === false) {
			return '';
		} else {
			return $option;
		}
	}

	/**
	 * gets plugin option value, or returns the empty string
	 */
	public static function get_blog_options() 
	{
		$options = get_option('uol_privacy_options');
		if ($options === false) {
			return self::get_default_blog_options();
		} else {
			return $options;
		}
	}

	/**
	 * gets default blog options
	 */
	public static function get_default_blog_options()
	{
		return array(
			"logged_in_users_only" => false,
			"allow_network_users" => false
		);
	}

    /**
     * add script to admin
     */
    public static function admin_scripts()
    {
        wp_enqueue_script( 'uol-privacy', plugins_url('/uol-private-blog.js', __FILE__), array('jquery') );
    }

}
uol_privacy::register();
endif;
?>
