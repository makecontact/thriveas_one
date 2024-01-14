<?php
/**
 * Plugin Name:       Thrive As One 
 * Description:       Custom libraries and code for the Thrive as one website.
 * Version:           1.0.0
 * Author:            MJ Shelley
 * Author URI:        https://mjshelley.com/
 * Author Email:      matt@mjshelley.com
 * Plugin URI: 		  https://thriveasone.ca
 * Text Domain:       thriveasone
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

//Definitions
define('TOA_GTM_TAG', 'GTM-KZN2NCH'); //Google Tag Manager
define('THRIVECART_API_MODE', 'live'); //live or test
define('MEMBERDECK_CANCELLED_GROUP', 3); //inside tao_profile.php we manually add them cancelled
define('MEMBERDECK_SUBSCRIBED_GROUP', 2); //inside tao_profile.php we use this to flag it as a new account

//The master series product ID in the gcc_product table for MemberDeck 
// --==-- Thrive Membership (Clearmind)
define('MEMBERDECK_MASTER_PRODUCT_ID', 4);

//ActiveCampaign integrations
define('TAO_AC_THRIVE_ENDPOINT', 'thriveasone');
define('TAO_AC_THRIVE_APIKEY', '33aed9aeb4cea34c0de94fddac9bf681e06ac6abf6c0f6d735540836630b42283d765c09');
define('TAO_AC_ACCOUNT', '478238487');
define('TAO_AC_EVENT_KEY', 'b66193628702880e580ee9d938ddf81b40a84fab');
define('TAO_AC_EVENTS', array(''));

//Clearmind ActiveCampaign API
define('TAO_AC_CLEARMIND_ENDPOINT', 'clearmind91109');
define('TAO_AC_CLEARMIND_APIKEY', '89243a99d0f314f5ee187b4b2d10cd57aa70e79d5e6ccf3d055135d4e5cd24489570e13f');
define('TAO_AC_CLEARMIND_TAGS', array('SOURCE-ThriveAsOne-Cart'));


//Which Sendy install to use for the email list?
define('TAO_SENDY_LIST', '7ftc2QvpKbR6S8os0ZVLoQ');
define('TAO_SENDY_LIST_REAL', 'DcuFxHJW55U22aLRb5sLLg');
define('TAO_SENDY', 'https://email.thriveasone.ca/subscribe');
define('TAO_SENDY_API','9EI3VMc5Q9zlXuD55Mxv');
define('MAXMIND_LOCATION', '/var/www/thriveasone/wp-content/uploads/maxmind/GeoIP/GeoLite2-City.mmdb');
define('TAO_GDPR_ZONES', array("AL","AD","AM","AT","BY","BE","BA","BG","CH","CY","CZ","DE","DK","EE","ES","FO","FI","FR","GB","GE","GI","GR","HU","HR","IE","IS","IT","LI","LT","LU","LV","MC","MK","MT","NO","NL","PL","PT","RO","RU","SE","SI","SK","SM","TR","UA","VA"));

//JavaScript Player Lib
define('TAO_PLAYER_LIB', '1.0.5');

//CMS
define('REMOTE_DB_PASS','uBqh%Ob040pKW8jNC863');
define('REMOTE_DB_USER','cms');
define('REMOTE_DB_NAME','cms');

define('TAO_FEEDBACK_EMAIL', 'support@thriveasone.ca');

define('TAO_DEVELOPERS', array(2));

//Defaults
define('TAO_DEFAULT_EXPERT', 'Thrive Community');

require 'vendor/autoload.php';
require 'tao-header.php';
require 'tao-profile.php';
require 'tao-ui.php';
require 'tao-programs.php';
require 'tao-integrations.php';

if (is_admin()) {
	if( function_exists('acf_add_options_page') ) {
		acf_add_options_page(array(
			'page_title' 	=> 'Settings',
			'menu_title'	=> 'Custom Settings',
			'menu_slug' 	=> 'bmd-general-settings',
			'capability'	=> 'edit_posts',
			'redirect'		=> false
		));		
	}
}

//URLs and configuration
function tao_qv($vars) {
	$vars[] = "__sendy";
	return $vars;
}
function tao_csr() {	
	add_rewrite_rule('webhook/sendy/?([^/]*)', 'index.php?__sendy=$matches[1]', 'top');
	if ( get_option( 'tao_url_rules' ) != '1.0.0' ) {
		flush_rewrite_rules();
		update_option( 'tao_url_rules', '1.0.0' );
	}
}
function tao_request() {
	global $wp, $wpdb;	
	if ( isset($wp->query_vars['__sendy'])) {
		tao_webhook_sendy($wp->query_vars['__sendy']);
	}
}
add_action('init', 'tao_csr');
add_filter('query_vars', 'tao_qv');
add_action('parse_request','tao_request');

//Error helper function
function tao_error($error) {
    if (WP_DEBUG == true) {
        $user_id = get_current_user_id();
        if (in_array($user_id, TAO_DEVELOPERS)) {
            $debug_file = WP_CONTENT_DIR . '/debug.log';
            $formatted_error = date('Y-m-d H:i:s') . ' ' . print_r($error, true) . PHP_EOL;
            file_put_contents($debug_file, $formatted_error, FILE_APPEND);
        }
    }
}

?>