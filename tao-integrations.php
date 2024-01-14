<?php

//Support events inside MemberDeck e.g. new sale
function tao_gcc_processed($event) {
    if ($event == 'order.success') {
        //Detect the sale of the main product
        $purchase_map = isset($_REQUEST['purchase_map']) ? $_REQUEST['purchase_map'] : false;
        //MemberDeck function for extracting authorised products
        $product_ids = gcc_is_product_supported($purchase_map);
        if (in_array(MEMBERDECK_MASTER_PRODUCT_ID, $product_ids)) {
            
            //ActiveCampaign integration for CLEARMIND!
            $contact_id = false;
            $apply_tags = false;

            //Add them to the main contact list in Clearmind
            $payload = array(
                'contact' => array(
                    'email' => $_REQUEST['customer']['email'],
                    'firstName' => $_REQUEST['customer']['first_name'],
                    'lastName' => $_REQUEST['customer']['last_name']
                )
            );
            $result = tao_call_active_campaign_post('contacts', $payload, TAO_AC_CLEARMIND_APIKEY, TAO_AC_CLEARMIND_ENDPOINT);
            
            //Extract out the customer's ID
            if ($result != false) {
                if (is_array($result)) {
                    $contact_id = $result['contact']['id'];
                    $apply_tags = true;
                } else if ($result === 422) {
                    //Email already exists, let's request the contact ID from ActiveCampaign
                    $email = urldecode($_REQUEST['customer']['email']);
                    $result = tao_call_active_campaign_get('contacts?email=' . $email, TAO_AC_CLEARMIND_APIKEY, TAO_AC_CLEARMIND_ENDPOINT);
                    if ($result !== false && is_array($result)) {
                        $contact_id = $result['contacts'][0]['id'];
                        $apply_tags = true;
                    }
                }
            }
            if ($contact_id !== false && $apply_tags == true) {
                //Get a list of tags in ActiveCampaign
                $tags = tao_refresh_clearmind_tags();
                //Create any missing tags in their ActiveCampaign
                $update_tags_required = false;
                foreach (TAO_AC_CLEARMIND_TAGS as $t) {
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
                                'description' => 'Added by Thrive'
                            )
                        );
                        $result = tao_call_active_campaign_post('tags', $payload, TAO_AC_CLEARMIND_APIKEY, TAO_AC_CLEARMIND_ENDPOINT);
                        $update_tags_required = true;
                    }
                }
                //Refresh tags after the update
                if ($update_tags_required == true) {
                    $tags = tao_refresh_clearmind_tags(true);
                }

                //Apply the tags by tag name to the contact
                foreach (TAO_AC_CLEARMIND_TAGS as $t) {
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
                        tao_call_active_campaign_post('contactTags', $payload, TAO_AC_CLEARMIND_APIKEY, TAO_AC_CLEARMIND_ENDPOINT);
                    }
                }
            }
        }
    }
}
add_action('gcc_processed', 'tao_gcc_processed', 1, 10);


//Update the tags
function tao_refresh_clearmind_tags($force = false) {
    $tags = get_transient('clearmind_ac_tags');
    if ($tags == '' || $force == true) {
        $result = tao_call_active_campaign_get('tags',  TAO_AC_CLEARMIND_APIKEY, TAO_AC_CLEARMIND_ENDPOINT);
        if (is_array($result)) {
            $tags = $result['tags'];
            set_transient('clearmind_ac_tags', $tags, 5 * 60); //Keep for 5 minutes
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

//Push an event into ActiveCampaign 
function tao_capture_active_campaign_event() {
    check_ajax_referer( 'tao_player', 'nonce' );
    $result = array(
        'message' => 'FAIL',
        'error' => true
    );
    $user = wp_get_current_user();
    $event = isset($_REQUEST['event']) ?  $_REQUEST['event'] : false;
    $event_data = isset($_REQUEST['event_data']) ? stripslashes($_REQUEST['event_data']) : false;
    if ($event !== false && in_array($event, TAO_AC_EVENTS)) {
        //Build the payload
        $post_fields = array(
            "actid" => TAO_AC_ACCOUNT,
            "key" => TAO_AC_EVENT_KEY,
            "event" => $event,
            "visit" => json_encode(array(
                "email" => $user->user_email,
            )),
        );
        if ($event_data != false) {
            $post_fields['eventdata'] = $event_data;
        }
        //Track the event
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, "https://trackcmp.net/event");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post_fields);
        $r = curl_exec($curl);
        //Set the return
        $result['message'] = 'SUCCESS';
        $result['error'] = false;
    }
    echo json_encode($result);
    exit();
}
add_action('wp_ajax_ac_event', 'tao_capture_active_campaign_event');


?>