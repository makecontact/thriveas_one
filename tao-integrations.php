<?php

//Subscribe
function tao_ac_subscribe($payload, $assign_tags, $api_key, $end_point) {
    $contact_id = false;
    $apply_tags = false;
    //Attempt the subscribe them
    $result = tao_call_active_campaign_post('contacts', $payload, $api_key, $end_point);
    //Extract out the customer's ID
    if ($result != false) {
        if (is_array($result)) {
            $contact_id = $result['contact']['id'];
            $apply_tags = true;
        } else if ($result === 422) {
            //Email already exists, let's request the contact ID from ActiveCampaign
            $email = urldecode($payload['contact']['email']);
            $result = tao_call_active_campaign_get('contacts?email=' . $email, $api_key, $end_point);
            if ($result !== false && is_array($result)) {
                $contact_id = $result['contacts'][0]['id'];
                $apply_tags = true;
                //We need to update this contact incase the details or values have changed
                tao_call_active_campaign_put('contacts/' . $contact_id, $payload, $api_key, $end_point);
            }
        }
    }
    if ($contact_id !== false && $apply_tags == true) {
        //Get a list of tags in ActiveCampaign
        $tags = tao_refresh_ac_tags(false, $api_key, $end_point, '?search=RealMovie');
        //Create any missing tags in their ActiveCampaign
        $update_tags_required = false;
        foreach ($assign_tags as $t) {
            $found = false;
            foreach ($tags as $tag) {
                if ($tag['tag'] == $t) $found = true;
            }
            //Tag missing, need to add it to their campaign
            if ($found == false) {
                $payload = array(
                    'tag' => array(
                        'tag' => $t,
                        'tagType' => 'contact',
                        'description' => 'Added automatically by ThriveAs.One'
                    )
                );
                $result = tao_call_active_campaign_post('tags', $payload, $api_key, $end_point);
                $update_tags_required = true;
            }
        }
        //Refresh tags after the update
        if ($update_tags_required == true) {
            $tags = tao_refresh_ac_tags(true, $api_key, $end_point, '?search=RealMovie');
        }

        //Apply the tags by tag name to the contact
        foreach ($assign_tags as $t) {
            $tag_id = false;
            foreach ($tags as $tag) {
                if ($tag['tag'] == $t) $tag_id = $tag['id'];
            }
            if ($tag_id !== false) {
                //Add the tag to the contact
                $payload = array(
                    'contactTag' => array(
                        'contact' => $contact_id,
                        'tag' => $tag_id
                    )
                );
                tao_call_active_campaign_post('contactTags', $payload, $api_key, $end_point);
            }
        }
    }
}


//Support events inside MemberDeck e.g. new sale
function tao_gcc_processed($event) {
    if ($event == 'order.success') {
        //Detect the sale of the main product
        $purchase_map = isset($_REQUEST['purchase_map']) ? $_REQUEST['purchase_map'] : false;
        //MemberDeck function for extracting authorised products
        $product_ids = gcc_is_product_supported($purchase_map);
        $found = false;
        //Search master products to see if we should register it with Clearmind
        foreach ($product_ids as $pid) {
            if (in_array($pid, MEMBERDECK_MASTER_PRODUCT_IDS)) {
                $found = true;
            }
        }
        if ($found == true) {

            /*
            //Build a list of tags we need to add for this sale
            $tags = TAO_AC_CLEARMIND_TAGS;
            if (in_array(MEMBERDECK_PID_MOVIE, $product_ids)) {
                //Buying the movie only
                array_push($tags, TAO_AC_CLEARMIND_TAG_MOVIE);
            } else {
                //Build the masterseries and maybe the awakening product
                array_push($tags, TAO_AC_CLEARMIND_TAG_MASTERSERIES);
                //Which price plan was used
                if ($_REQUEST['order']['charges'][0]['payment_plan_id'] == TAO_THRIVE_PAYMENTPLAN_SERIES) {
                    array_push($tags, TAO_AC_CLEARMIND_TAG_SERIESONLY);
                } else {
                    array_push($tags, TAO_AC_CLEARMIND_TAG_AWAKENING);
                }
            }
            //Opt in tag
            if ($_REQUEST['customer']['checkbox_confirmation'] == true) {
                array_push($tags, TAO_AC_CLEARMIND_TAG_OPTIN);
            }
            //Add them to the main contact list in Clearmind
            $payload = array(
                'contact' => array(
                    'email' => $_REQUEST['customer']['email'],
                    'firstName' => $_REQUEST['customer']['first_name'],
                    'lastName' => $_REQUEST['customer']['last_name']
                )
            );
            tao_ac_subscribe($payload, $tags, TAO_AC_CLEARMIND_APIKEY, TAO_AC_CLEARMIND_ENDPOINT);
            */

        }
    }
}
add_action('gcc_processed', 'tao_gcc_processed', 1, 10);


//Update the tags
// $force - boolean, ignore transient and force a refresh
//
function tao_refresh_ac_tags($force, $api_key, $end_point, $filter = '') {
    $tags = get_transient($end_point . '_ac_tags');
    if ($tags == '' || $force == true) {
        $result = tao_call_active_campaign_get('tags' . $filter,  $api_key, $end_point);
        if (is_array($result)) {
            $tags = $result['tags'];
            set_transient($end_point . '_ac_tags', $tags, 5 * 60); //Keep for 5 minutes
        } else {
            //Return empty; actually an error
            $tags = array();
        }
    }
    return $tags;
}

//Call ActiveCampaign Post
function tao_call_active_campaign_post($command, $payload, $api_key, $end_point) {
    $response = wp_remote_post('https://' . $end_point . '.api-us1.com/api/3/' . $command, array(
        'headers' => array(
            'Api-Token' => $api_key,
            'Content-Type' => 'application/json',
        ),
        'body' => json_encode($payload)),
    );
    if (!is_wp_error($response)) {
        $code = wp_remote_retrieve_response_code($response);
        if ($code === 201) {
            //Return the result
            return json_decode(wp_remote_retrieve_body($response), true);
        } else {
            //Return the error code
            return $code;
        }
    } else {
        //Fail
        return false;
    }
}

//Call ActiveCampaign Get
function tao_call_active_campaign_get($command, $api_key, $end_point) {
    $response = wp_remote_get('https://' . $end_point . '.api-us1.com/api/3/' . $command, array(
        'headers' => array(
            'Api-Token' => $api_key,
            'Content-Type' => 'application/json',
        )
    ));
    if (!is_wp_error($response)) {
        $code = wp_remote_retrieve_response_code($response);
        if ($code === 200) {
            //Return the result
            return json_decode(wp_remote_retrieve_body($response), true);
        } else {
            //Return the error code
            return $code;
        }
    } else {
        //Fail
        return false;
    }
}

//Call ActiveCampaign Put
function tao_call_active_campaign_put($command, $payload, $api_key, $end_point) {
    $response = wp_remote_get('https://' . $end_point . '.api-us1.com/api/3/' . $command, array(
        'headers' => array(
            'Api-Token' => $api_key,
            'Content-Type' => 'application/json',
        ),
        'body' => json_encode($payload),
        'method' => 'PUT'
        )
    );
    if (!is_wp_error($response)) {
        $code = wp_remote_retrieve_response_code($response);
        if ($code === 200) {
            //Return the result
            return json_decode(wp_remote_retrieve_body($response), true);
        } else {
            //Return the error code
            return $code;
        }
    } else {
        //Fail
        return false;
    }
}

//Push an event into ActiveCampaign 
function tao_handle_active_campaign_event() {
    check_ajax_referer( 'tao_player', 'nonce' );
    $result = array(
        'message' => 'SUCCESS',
        'error' => false
    );
    $pct = isset($_REQUEST['pct']) ?  intval($_REQUEST['pct']) : 0;
    $user = wp_get_current_user();
    //Lookup the tracking behaviour
    if (isset(TAO_AC_THRIVE_MOVIE_TRACKING[$pct]) && TAO_AC_THRIVE_MOVIE_TRACKING[$pct] != '') {
        $tracking = TAO_AC_THRIVE_MOVIE_TRACKING[$pct];
        foreach ($tracking as $t) {
            if ($t == 'event') {
                //Add all the events
                foreach (TAO_AC_EVENTS as $e) {
                    //Event tracking
                    $post_fields = array(
                        "actid" => TAO_AC_ACCOUNT,
                        "key" => TAO_AC_EVENT_KEY,
                        "event" => $e, //Send over the event's name
                        "eventdata" => $pct . '%', //Send over the percentage
                        "visit" => json_encode(array(
                            "email" => $user->user_email,
                        )),
                    );
                    //Track the event
                    $curl = curl_init();
                    curl_setopt($curl, CURLOPT_URL, "https://trackcmp.net/event");
                    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($curl, CURLOPT_POST, true);
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $post_fields);
                    $r = curl_exec($curl);
                }
            } else {
                //Add the tag's name to an array for verification
                $assign_tags = array();
                array_push($assign_tags, $t);
                //Build the payload for subscribing and tagging them;
                //will auto insert into AC too but they should already be there.
                $payload = array(
                    'contact' => array(
                        'email' => $user->user_email
                    )
                );
                tao_ac_subscribe($payload, $assign_tags, TAO_AC_THRIVE_APIKEY, TAO_AC_THRIVE_ENDPOINT);
            }
        }
    }
    echo json_encode($result);
    exit();
}
add_action('wp_ajax_ac_event', 'tao_handle_active_campaign_event');


//Programs checkout assist for logged in users
function tao_program_checkout() {
    check_ajax_referer( 'tao_global', 'n' );
    $result = array(
        'error' => true,
        'url' => ''
    );
    $ep = isset($_REQUEST['ep']) ? $_REQUEST['ep'] : '';
    $result['url'] = $_REQUEST['ep'];
    if ($ep != '') {
        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            //Load the meta
            $first_name = get_user_meta($user->ID, 'first_name', true);
            $last_name = get_user_meta($user->ID, 'last_name', true);
            $country_code = get_user_meta($user->ID, 'country', true);
            //Work out extender
            $extend = '?';
            if (strpos($ep, '?') !== false) {
                $extend = '&';
            }
            //Prepare passthrough data
            $pt =  $extend . 'passthrough[customer_firstname]=' . urlencode($first_name);
            $pt .= '&passthrough[customer_lastname]=' . urlencode($last_name);
            $pt .= '&passthrough[customer_email]=' . urlencode($user->user_email);
            $pt .= '&passthrough[customer_address_country]=' . urlencode($country_code);
            $result['url'] = $ep . $pt;
            $result['error'] = false;
        } else {
            $result['error'] = false;
        }
    }
    echo json_encode($result);
    exit();         
}
add_action('wp_ajax_tao_program_checkout', 'tao_program_checkout');
add_action('wp_ajax_nopriv_tao_program_checkout', 'tao_program_checkout');

?>