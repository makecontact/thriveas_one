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

?>