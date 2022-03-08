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
define('THRIVECART_API_MODE', 'test'); //live or test
define('MEMBERDECK_CANCELLED_GROUP', 3); //inside tao_profile.php we manually add them cancelled
define('MEMBERDECK_SUBSCRIBED_GROUP', 2); //inside tao_profile.php we use this to flag it as a new account

//Which Sendy install to use for the email list?
define('TAO_SENDY_LIST', '7ftc2QvpKbR6S8os0ZVLoQ');
define('TAO_SENDY', 'https://email.thriveasone.ca/subscribe');
define('TAO_SENDY_API','9EI3VMc5Q9zlXuD55Mxv');
define('MAXMIND_LOCATION', '/var/www/thriveasone/wp-content/uploads/maxmind/GeoIP/GeoLite2-City.mmdb');
define('TAO_GDPR_ZONES', array("AL","AD","AM","AT","BY","BE","BA","BG","CH","CY","CZ","DE","DK","EE","ES","FO","FI","FR","GB","GE","GI","GR","HU","HR","IE","IS","IT","LI","LT","LU","LV","MC","MK","MT","NO","NL","PL","PT","RO","RU","SE","SI","SK","SM","TR","UA","VA"));

//CMS
define('REMOTE_DB_PASS','uBqh%Ob040pKW8jNC863');
define('REMOTE_DB_USER','cms');
define('REMOTE_DB_NAME','cms');

//Defaults
define('TAO_DEFAULT_EXPERT', 'Thrive Community');

require 'vendor/autoload.php';
require 'tao-header.php';
require 'tao-profile.php';
require 'tao-ui.php';
require 'tao-programs.php';

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
?>