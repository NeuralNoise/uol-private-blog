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
	public static function register()
	{
		add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));
		add_action( 'wp', array(__CLASS__, 'force_member_login_init') );
		
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
	 * uses add_submenu_page to add a menu item to the Settings menu
	 */
    public static function add_admin_menu()
    {
		/* adjust capability according to activation status of plugin */
    	add_submenu_page( 'options-general.php', 'Privacy Settings', 'Privacy Settings', 'manage_options', 'uol-privacy', array(__CLASS__, 'get_admin_page'));
    }

    /**
     * options page
     */
    public static function get_admin_page()
    {
        /* check that the user has the required capability */ 
        if (!current_user_can('manage_options'))
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
	    	    		$ipstr = implode("|", $valid_ips);
	    	    		update_option('uol-privacy', $ipstr);
	    	    	}
	    	    }
	    	}
	    }
	    $ips = self::get_option();
	    printf('<div class="wrap"><div id="icon-options-general" class="icon32"></div><h2>%s</h2><form method="post" action="">', __('Privacy settings'));
        printf('<input type="hidden" name="ip_allow_list" id="ip_allow_list" value="%s" />', wp_create_nonce('ip_allow_list_nonce'));
        printf('<p>%s</p><p><textarea name="ip_list" id="ip_list" cols="60" rows="20">%s</textarea></p>', __('Input a list of IP addresses, separated by linebreaks, to allow onto the site without the user having to log in. Partial IP addresses are OK'), str_replace("|", "\n", $ips));
        printf('<p><input type="submit" name="submit" class="button-primary" value="%s" /></p></form></div>', __('Save Changes'));
   }

    public static function is_sitewide()
    {
    	return (is_multisite() && is_plugin_active_for_network(plugin_basename(__FILE__)));
    }

    /**
	 * gets plugin option value, or returns the empty string
	 */
    public static function get_option() 
    {
    	$option = get_option('uol-privacy');
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
