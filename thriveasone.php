<?php
/**
 * Plugin Name:       Thrive As One 
 * Description:       Custom libraries and code for the Thrive as One website.
 * Version:           1.0.0
 * Author:            MJ Shelley
 * Author URI:        https://mjshelley.com/
 * Author Email:      matt@mjshelley.com
 * Plugin URI: 		  https://thriveas.one
 * Text Domain:       thriveasone
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

//Definitions
define('TOA_GTM_TAG', 'GTM-T4LLP8W5'); //Google Tag Manager

define('THRIVECART_API_MODE', 'live'); //live or test
define('MEMBERDECK_CANCELLED_GROUP', 3); //inside tao_profile.php we manually add them cancelled
define('MEMBERDECK_SUBSCRIBED_GROUP', 2); //inside tao_profile.php we use this to flag it as a new account


/*


Commented out ActiveCampaign integrations because we will be working on this
later when we scope out the functions of the new site.
- Subscribing when someone new joins.
- Tagging which videos they bought
- Watch positions for each video
- NO Clearmind integration anymore


//The master series product ID in the gcc_product table for MemberDeck 
define('MEMBERDECK_MASTER_PRODUCT_IDS', array(5, 6));
define('MEMBERDECK_PID_MOVIE', 6);
define('MEMBERDECK_PID_MASTERSERIES', 5);

//ActiveCampaign integrations
define('TAO_AC_THRIVE_ENDPOINT', 'thriveasone');
define('TAO_AC_THRIVE_APIKEY', '33aed9aeb4cea34c0de94fddac9bf681e06ac6abf6c0f6d735540836630b42283d765c09');
define('TAO_AC_ACCOUNT', '478238487');
define('TAO_AC_EVENT_KEY', 'b66193628702880e580ee9d938ddf81b40a84fab');
define('TAO_AC_EVENTS', array('Watched Real Movie'));
define('TAO_AC_THRIVE_SUB_TAGS', array('RealMovie','SOURCE-RealMovie-Sub'));
define('TAO_AC_THRIVE_SUB_TEMPLATE', 'SOURCE-RealMovie-Sub-');

//Feedback locations ([tao_subscribe list=""]) mapped to ActiveCampaign fields
define('TAO_AC_THRIVE_FEEDBACK_MAP', array(
	'4' => 'MOVIE_REVIEW_VANCOUVER',
	'5' => 'MOVIE_REVIEW_UK',
	'6' => 'MOVIE_REVIEW'
));

//How to handle events and tags for ActiveCampaign while watching the movie
define('TAO_AC_THRIVE_MOVIE_TRACKING', array(
	'0' => array('event','SOURCE-RealMovie-Watched-0'),
	'10' => array('event'),
	'20' => array('event'),
	'30' => array('event'),
	'40' => array('event'),
	'50' => array('event','SOURCE-RealMovie-Watched-50'),
	'60' => array('event'),
	'70' => array('event'),
	'80' => array('event'),
	'90' => array('event','SOURCE-RealMovie-Watched-End')
));
*/

//JavaScript Player Lib
define('TAO_PLAYER_LIB', '1.0.5');

//CMS
define('REMOTE_DB_PASS','uBqh%Ob040pKW8jNC863');
define('REMOTE_DB_USER','cms');
define('REMOTE_DB_NAME','cms');

define('TAO_FEEDBACK_EMAIL', 'hello@thriveas.one');

define('TAO_DEVELOPERS', array(2));

//Defaults
define('TAO_DEFAULT_EXPERT', 'Thrive Community');

require 'vendor/autoload.php';
require 'tao-header.php';
require 'tao-profile.php';
require 'tao-ui.php';
require 'tao-programs.php';
//require 'tao-integrations.php';

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