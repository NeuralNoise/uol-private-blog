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
            add_action( 'wp', array('uol_privacy', 'force_member_login_init') );
        }

        function force_member_login_init() 
    {
        /* If the user is logged in, then abort */
        if ( current_user_can('read') ) return;
                /* if the user appears to be on the campus network, then abort */
                $ips = array(
                    "129.11",
                        "194.82.12",
                        "194.82.13",
                        "194.82.14",
                        "194.82.15",
                        "194.80.232",
                        "194.80.233",
                        "194.80.234",
                        "194.80.235"
                );
                $is_on_campus = false;
                foreach ($ips as $ip) {
                    if (strpos($_SERVER["REMOTE_ADDR"], $ip) === 0) {
                            $is_on_campus = true;
                        }
                }
                if ($is_on_campus) return;
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
uol_privacy::register();
endif;
?>
