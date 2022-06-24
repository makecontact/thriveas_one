<?php

    use GeoIp2\Database\Reader;

   //Location service for cookie consent
   function toa_location() {
 		//Grab the country code for this user
         $result = array('countryCode' => '', 'countryName' => '');
         try {
            $upload_dir = wp_upload_dir();
            $geo_ip_reader = new Reader($upload_dir['basedir'] . '/maxmind/GeoIP/GeoLite2-City.mmdb');
            $record = $geo_ip_reader->city(tao_get_client_ip());
            $result['countryCode'] = $record->country->isoCode;
            $result['countryName'] = $record->country->name;
         } catch (Exception $e) {
             $result['countryCode'] = '';
             $result['countryName'] = '';
         }
         echo json_encode($result);
         exit();	       
   }
   add_action('wp_ajax_nopriv_tao_country_code', 'toa_location');
   add_action('wp_ajax_tao_country_code', 'toa_location');


   //Get the client's IP address
   function tao_get_client_ip() {
        $ip = '';
        if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
            //check ip from share internet
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            //to check ip is pass from proxy
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        //Grab the first IP from the list - CDN fix
        if (strpos($ip, ',') !== false) {
            $ips = explode(',', $ip);
            $ip = trim($ips[0]); 	
        }
        return $ip;
    }

    //External script such as Cookie consent
    function toa_enqueue_scripts() {
        $json = file_get_contents(plugin_dir_path(__FILE__) .  '/cookie.json');
        //Global scripts such as the affiliate programme
        wp_enqueue_script( 'toaglobal', plugin_dir_url(__FILE__) . 'js/super.js', array(), '1', true);
        $nonce = wp_create_nonce('tao_global');
        $ajax = admin_url('admin-ajax.php');
        wp_add_inline_script( 'toaglobal', 'window.toaglobal={n:"' . $nonce . '",u:"' . $ajax . '"}' );

        //Cookie consent from CDN
        wp_enqueue_style( 'cc', '//cdn.jsdelivr.net/npm/cookieconsent@3/build/cookieconsent.min.css', array(), '3' );
        wp_enqueue_script( 'cc', '//cdn.jsdelivr.net/npm/cookieconsent@3/build/cookieconsent.min.js', array(), '4', true);
        wp_add_inline_script( 'cc', 'window.addEventListener("load", function(){window.cookieconsent.initialise(' . $json . ')});' );       
    }
    add_action('wp_enqueue_scripts', 'toa_enqueue_scripts');

    /*
        Google Tag Manager
    */
    function toa_tag_manager_header() {
        echo "<!-- Google Tag Manager -->";
        echo "<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':";
        echo "new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],";
        echo "j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=";
        echo "'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);";
        echo "})(window,document,'script','dataLayer','" . TOA_GTM_TAG . "');</script>";
        echo "<!-- End Google Tag Manager -->";
    }
    add_action('wp_head', 'toa_tag_manager_header', 10, 0);

    //Google tag manager no script tag
	function toa_tag_manager_no_script() {
		$default = get_option('intercpt_default', array());
		echo "<!-- Google Tag Manager (noscript) -->
<noscript><iframe src=\"https://www.googletagmanager.com/ns.html?id=" . TOA_GTM_TAG . "\"
height=\"0\" width=\"0\" style=\"display:none;visibility:hidden\"></iframe></noscript>
<!-- End Google Tag Manager (noscript) -->";		
	}
    add_action( 'x_before_site_begin', 'toa_tag_manager_no_script', 1, 0 );


    //Open function to check affiliate is valid
    function toa_affcheck() {
        check_ajax_referer( 'tao_global', 'n' );
        $result = array(
            'error' => false,
            'result' => false
        );
        $aff = isset($_REQUEST['affilate']) ? strtolower(sanitize_text_field($_REQUEST['affilate'])) : '';
        //Check if there's a transient
        if (strlen($aff) >= 3 && strlen($aff) <= 30) {
            $valid_add = get_transient('tao_aff_' . $aff);
            if ($valid_add != '') {
                //Found a cached result, return true
                if ($valid_add == 1) $result['result'] = $aff;
            } else {
                //Check with ThriveCart if this is a valid affiliate ID
                $default = get_option('mjs_gcc_defaults', array());
                $thrive_api_token = isset($default['thrive_api_token']) ? $default['thrive_api_token'] : false;
                if ($thrive_api_token != false) {
                    $search = tao_get_tc($thrive_api_token, 'affiliates', 'perPage=1&page=1&query=' . urlencode($aff), 'GET');
                    if (property_exists($search,'error')) {
                        //Thrive is not configured yet!
                        error_log('Affilates not configured: ' . $search->error);
                    } else {
                        //Check the affiliate is correct
                        if (property_exists($search,'affiliates')) {
                            $found = false;
                            foreach ($search->affiliates as $a) {
                                if ($a->affiliate_id == $aff) {
                                    $found = $a->affiliate_id;
                                    break;
                                }
                            }
                            if ($found != false) {
                                $result['result'] = $found;
                                //Remember the result for 7 days
                                set_transient('tao_aff_' . $aff, 1, WEEK_IN_SECONDS);
                            } else {
                                //Remeber the negative result for a day
                                set_transient('tao_aff_' . $aff, 0, DAY_IN_SECONDS);
                            }
                        }
                    }
                }
            }
        }
        echo json_encode($result);
        exit();
    }
    add_action('wp_ajax_nopriv_affcheck', 'toa_affcheck');
    add_action('wp_ajax_affcheck', 'toa_affcheck');

?>