<?php
/*
Plugin Name: University of Leeds privacy plugin
Plugin URI: http://www.pvac.leeds.ac.uk/
Description: This plugin restricts access to a blog so you either have to be logged in or on the campus network in order to access it
Version: 1.0
Author: Peter Edwards
*/
if (!class_exists("uol_privacy")) :
/**
 * class to redirect users to the wordpress login page if they are either not logged in or 
 * not on the campus network
 */
class uol_privacy
{
	public static function register()
	{
		if (self::is_sitewide()) {
	        /* add menu to Network admin page */
	        add_action('network_admin_menu', array('uol_privacy', 'addAdminMenu'));
	    } else {
	    	add_action('admin_menu', array('uol_privacy', 'addAdminMenu'));
	    }
		add_action( 'wp', array('uol_privacy', 'force_member_login_init') );
		
	}

	function force_member_login_init() 
	{
		/* If the user is logged in, then abort */
		if ( current_user_can('read') ) return;

		/* if the user appears to have a valid IP address, abort */
		$ipstr = self::get_option();
		if ( !empty($ipstr) ) {
			$ips = explode("|", $ipstr);
			$ip_is_allowed = false;
			foreach ($ips as $ip) {
				if (strpos($_SERVER["REMOTE_ADDR"], $ip) === 0) {
					$ip_is_allowed = true;
				}
			}
			if ($ip_is_allowed) return;
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

	/**
	 * uses add_submenu_page to add a menu item to the Settings menu (on the network admin dashboard or the blog dashboard)
	 */
    public static function addAdminMenu()
    {
		/* adjust capability according to activation status of plugin */
		$cap = (self::is_sitewide())? 'manage_network_options': 'manage_options';
    	add_submenu_page( 'settings.php', 'Privacy', 'Privacy', $cap, 'uol-privacy', array('uol_privacy', 'get_admin_page'));
    }

    /**
     * options page
     */
    public static function get_admin_page()
    {
        /* check that the user has the required capability */ 
		$cap = (self::is_sitewide())? 'manage_network_options': 'manage_options';
        if (!current_user_can($cap))
        {
            wp_die( __('You do not have sufficient permissions to access this page.') );
        }
        /* save options */
        if (isset($_POST["ip_list"])) {
        	if ( wp_verify_nonce( $_POST['ip_allow_list'], 'ip_allow_list_nonce' )) {
	        	if (trim($_POST["ip_list"]) != '') {
		        	$ips = explode("\n", $_POST["ip_list"]);
		        	$valid_ips = array();
	    	    	foreach ($ips as $ip) {
	    	    		$isValid = true;
	    	    		/* make sure it is only numbers and dots */
	    	    		$ip = trim(preg_replace('/[^0-9\.]*/', '', $ip), ". \t\n\r\0\x0B");
	    	    		/* split into quads */
	    	    		$quads = explode(".", $ip);
	    	    		/* test each quad */
	    	    		foreach ($quads as $quad) {
	    	    			if (!preg_match('/(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)/', $quad)) {
	    	    				$isValid = false;
	    	    			}
	    	    		}
	    	    		if ($isValid) {
	    	    			$valid_ips[] = $ip;
	    	    		}
	    	    	}
	    	    	if (count($valid_ips)) {
	    	    		$ipstr = implode("|", $valid_ips);
	    	    		if (self::is_sitewide()) {
	    	    			update_site_option('uol_privacy', $ipstr);
	    	    		} else {
	    	    			update_option('uol_privacy', $ipstr);
	    	    		}
	    	    	}
	    	    }
	    	}
	    }
	    $ips = self::get_option();
	    printf('<div class="wrap"><div id="icon-settings" class="icon32"></div><h2>%s</h2><form name="custom-login-page-settings-form" method="post" action="">', __('Privacy settings'));
        printf('<input type="hidden" name="ip_allow_list" id="ip_allow_list" value="%s" />', wp_create_nonce('ip_allow_list_nonce'));
        printf('<p>%s</p><p><textarea name="ip_allow_list" id="ip_allow_list">%s</textarea></p>', __('Input a list of IP addresses, separated by linebreaks, to allow onto the site without the user having to log in. Partial IP addresses are OK'), explode("|", $ips));
        printf('<p><input type="submit" name="submit" class="button-primary" value="%s" /></p></form></div>', __('Save Changes'));
    }

    public static function is_sitewide()
    {
    	return (is_multisite() && is_plugin_active_for_network(plugin_basename(__FILE__)));
    }

    /**
	 * gets default plugin options
	 */
    public static function get_option() 
    {
    	if (self::is_sitewide()) {
    		$option = get_site_option('uol_privacy');
    	} else {
    		$option = get_option('uol_privacy');
    	}
    	if ($option === false) {
    		return '';
    	} else {
	        return $option;
	    }
    }

}
uol_privacy::register();
endif;
?>
