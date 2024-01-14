<?php
    function tao_profile() {
        //Load existing profile data
        $user = wp_get_current_user();
        $meta = get_userdata($user->ID);
        wp_enqueue_script( 'tao_account', plugin_dir_url(__FILE__) . '/js/account.js', '', '1.0.0', false);	
        if ($user != false) {
            $html = '<div id="tao_profile_editable" class="tao_profile">';    
            //First name
            $html .= '<div id="tao_set_firstname">';
            $html .= '<label for="tao_profile_firstname">First Name<br>';
            $html .= '<span class="tao_text_field">' . $meta->first_name . '</span>';
            $html .= '</label></div>';               
            //Last name
            $html .= '<div id="tao_set_lastname">';
            $html .= '<label for="tao_profile_lastname">Last Name<br>';
            $html .= '<span class="tao_text_field">' . $meta->last_name . '</span>';
            $html .= '</label></div>';           
            //Email
            $html .= '<div id="tao_set_email">';
            $html .= '<label for="tao_profile_email">Email<br>';
            $html .= '<span class="tao_text_field">' . strtolower($user->user_email) . '</span>';
            $html .= '</label></div>';
            //Password 
            $html .= '<div id="tao_set_password">';
            $html .= '<label for="tao_profile_password">Password<br>';
            $html .= '<span class="tao_text_field">*********</span>';
            $html .= '</label></div>';
            //Edit link
            $html .= '<div><a id="tao_edit_profile" class="tao_main_link">Edit Profile</a></div>';
            $html .= '</div>';
            //Output essential script data
            $nonce = wp_create_nonce('tao_account');
            $set_password = get_user_meta($user->ID, 'password_set', true);
            //Add essential data to the script
            wp_localize_script(
                'tao_account', 
                'tao_account',
                array(
                    'url' => admin_url( 'admin-ajax.php' ),
                    'nonce' => $nonce,
                    'firstname' => $meta->first_name,
                    'lastname' => $meta->last_name,
                    'email' => $user->user_email,
                    'ready' => $set_password
                )
            );
        }
        return $html;
    }
    add_shortcode('tao_profile', 'tao_profile');


    //Get the first name of the user
    function tao_first_name() {
        $name = '';
        $user = wp_get_current_user();
        if ($user != false) {
            $name = ucfirst($user->first_name);
        }
        return $name;
    }
    add_shortcode('tao_first_name','tao_first_name');

    //Prevent the change password email being fired
    add_filter( 'send_password_change_email', '__return_false' );

    //Handle updating the profile
    function tao_edit_profile() {
        global $wpdb;
        check_ajax_referer( 'tao_account', 'nonce' );
        $result = array(
            'data' => '',
            'message' => 'OK',
            'error' => false
        );
        //Handle the password first
        $user = wp_get_current_user();
        $meta = get_userdata($user->ID);
        $user_id = $user->ID;
        //Read in data
        $set_password = get_user_meta($user->ID, 'password_set', true);
        $firstname = isset($_POST['firstname']) ? $_POST['firstname'] : '';
        $lastname = isset($_POST['lastname']) ? $_POST['lastname'] : '';
        $email = isset($_POST['email']) ? $_POST['email'] : '';
        $new = isset($_POST['new']) ? $_POST['new'] : '';
        $current = isset($_POST['current']) ? $_POST['current'] : '';
        //Does the process require re-authentication?
        $reauth = false;
        //Build user update array
        $user_update = array();        
        //Set the password
        if ($new != '') {
            //We need to check if the password is correct
            if ($set_password != '') {
                //Authenticate with the existing user_login 
                $check = wp_authenticate_username_password( NULL, $user->user_login , $current);
                if (is_wp_error($check)) {
                    $result = array(
                        'data' => '',
                        'message' => 'PASSWORD',
                        'error' => true
                    );                    
                    echo json_encode($result);
                    exit(); 
                } 
            }
            //Continue with setting the password
            $user_update['user_pass'] = $new;
            //Activation route complete
            update_user_meta($user->ID, 'password_set', true);
        }
        //Firstname
        if ($firstname != $meta->first_name) {
            $user_update['first_name'] = $firstname;
            $user_update['nickname'] = $firstname;
            //Create a unique SEO friendly name
            $unique_name = tao_generate_unique_username($firstname);
            $user_update['user_nicename'] = $unique_name;
            /*
            //Results in logging them out
            $wpdb->update(
                $wpdb->prefix . 'users',
                array(
                    'user_login' => $unique_name
                ),
                array(
                    'ID' => get_current_user_id()
                ),
                array(
                    '%s'
                ),
                array(
                    '%d'
                )
            );
            $reauth = true;
            */
        }
        //Last name
        if ($lastname != $meta->last_name) {
            $user_update['last_name'] = $lastname;
        }
        //Email
        if ($email != $user->user_email) {
            $user_update['user_email'] = $email;
        }        
        //Now set the object
        if (!empty($user_update)) {
            //Attach the ID
            $user_update['ID'] = get_current_user_id();
            $user_data = wp_update_user($user_update);
            //Set the result
            $result = array(
                'data' => '',
                'message' => 'UPDATED',
                'error' => false
            );            
        }
        //Process signed them out - we can sign them back in IF they were setting the password too
        if ($reauth) {
            /*
            $user_auth = wp_signon( array(
                'user_login' => $user->user_email,
                'user_password' => $new,
                'remember' => true
            ), true );
            */            
        }
        echo json_encode($result);
        exit();
    }
    add_action('wp_ajax_tao_edit_profile', 'tao_edit_profile');

    //Create a unique username
    function tao_generate_unique_username( $username ) {
        $username = sanitize_title( $username );
        static $i;
        if ( null === $i ) {
            $i = 1;
        } else {
            $i ++;
        }
        if ( ! username_exists( $username ) ) {
            return $username;
        }
        $new_username = sprintf( '%s-%s', $username, $i );
        if ( ! username_exists( $new_username ) ) {
            return $new_username;
        } else {
            return call_user_func( __FUNCTION__, $username );
        }
    }

    /*
        ThriveCart *Aug 2021* relies on the email address as the ID
        Which the member can easily change - without updating their
        account. So, we capture the initial email the account is
        created with and use the current profile email as a backup.

        If ThriveCart allows us to interrogate with customer ID's
        in the future this can be changed.
    */
    function tao_capture_initial_email($user_id, $userdata) {
        //Store the email first used
        update_user_meta($user_id, '_thrivecart_email', $userdata['user_email']);
    }
    add_action('user_register', 'tao_capture_initial_email', 2, 10);


    //Membership functions
    function tao_membership() {
        global $wpdb;
        $user = wp_get_current_user();
        if ($user != false) {
            //Load membership
            $m = tao_request_membership($user);
            $html = '<div class="tao_profile" id="tao_membership">';
            if (isset($m->customer) ) {
                //Check cancellation status
                $has_cancelled = false;
                $cancelled = $wpdb->get_row('SELECT * FROM ' . $wpdb->prefix . 'groups_user_group WHERE user_id = ' . $user->ID . ' AND group_id = 3');
                if ($cancelled != null) {
                    $has_cancelled = true;
                }
                //Check access status
                $has_access = false;
                $subscriber = $wpdb->get_row('SELECT * FROM ' . $wpdb->prefix . 'groups_user_group WHERE user_id = ' . $user->ID . ' AND group_id = 2');
                if ($subscriber != null) {
                    $has_access = true;
                }
                //Begin block
                $html .= '<div id="tao_membership">';
                $html .= '<label style="margin-bottom: 0.5em;" for="tao_profile_membership">Membership<br>';
                //Track if we've found a subscription
                $found_plan = false;
                $has_cancelled_plan = false;
                $found_single = false;
                $subscription = false;
                $purchase = false;
                //Active subscription?
                if (isset($m->subscriptions)) {
                    if (count($m->subscriptions) > 0) {
                        foreach ($m->subscriptions as $sub) {
                            //Only interested in the main product
                            if ($sub->status == 'active' && $sub->item_type == 'product' && $sub->item_id = '1') {
                                //Frequence of the payment
                                $frequency = 'Yearly';
                                if ($sub->frequency == 'month') {
                                    $frequency = 'Monthly';
                                }
                                $html .= '<div id="tao_main_details">';
                                $html .= '<span class="tao_text_field">Active (' . $frequency . ')</span></label>';
                                //Link to Pause Membership
                                $ex_date = '';
                                $expiration = $wpdb->get_row('SELECT date_format(expiration,"%M %e, %Y ") AS edate  FROM ' . $wpdb->prefix . 'gcc_group_expire WHERE user_id = ' . $user->ID . ' AND group_id = 2');
                                if ($expiration != null) {
                                    $ex_date = $expiration->edate;
                                }
                                $html .= '<div style="margin-bottom: 1.5em;"><a data-expire="' . $ex_date . '" id="tao_cancel" class="tao_main_link">Pause Membership</a></div>';
                                //Format billing amount
                                $value = '0.00 CAD';
                                if (isset($sub->events) && count($sub->events) != 0) {
                                    $currency = 'CAD $';
                                    switch ($sub->currency) {
                                        case 'GBP':
                                            $currency = '£';
                                            break;
                                        case 'USD':
                                            $currency = 'USD $';
                                            break;
                                        case 'EUR':
                                            $currency = 'EUR ';
                                            break;                                            
                                    }
                                    $value = $currency . number_format($sub->events[0]->amount / 100, 2);
                                } 
                                //Format the next date
                                $next = date_create($sub->next_payment);
                                //Payment Details 
                                $html .= '<label style="margin-bottom: 0.5em;" for="tao_profile_membership">Payment Details<br>';
                                $html .= '<span class="tao_text_field">Last Payment ' . $value . '</span><br>';
                                $html .= '<span class="tao_text_field_small">Your next billing date is ' . date_format($next, "F j, Y") . '</span>';
                                $html .= '</label>';                               
                                //Edit Billing details
                                $html .= '<div style="margin-bottom: 1.5em;"><a id="tao_billing_edit" href="https://thriveasone.thrivecart.com/updateinfo/" class="tao_main_link">Edit Billing Details</a></div>';
                                $html .= '</div>';//Main details
                                //Plan Details - hidden
                                $html .= '<div id="tao_plan_details" style="display: none;">';
                                //Cancelled product - only shown to customers clicking cancel
                                $html .= '<span class="tao_text_field">Paused</span></label>';
                                $html .= '<div style="margin-bottom: 1.5em;">[cs_gb name="rejoin-button"]</div>';
                                //Show the expiration data
                                if ($expiration != null) {
                                    //Expiration notice
                                    $html .= '<label style="margin-bottom: 0.5em;" for="tao_profile_membership">Plan Details<br>';
                                    $html .= '<span class="tao_text_field">Expires ' . $expiration->edate . '</span>';
                                    $html .= '</label>';
                                }
                                $html .= '</div>';                                
                                $found_plan = true;
                                break;
                            } else if ($sub->status == 'cancelled' && $sub->item_type == 'product' && $sub->item_id = '1') {
                                $has_cancelled_plan = true;
                            }
                        }
                        //Deal with a cancelled plan
                        if ( ($found_plan == false && $has_cancelled == true) || ($found_plan == false && $has_cancelled_plan == true)) {
                            foreach ($m->subscriptions as $sub) {
                                //Only interested in the main product
                                if ($sub->status == 'cancelled' && $sub->item_type == 'product' && $sub->item_id = '1') {
                                    //Paused title
                                    $html .= '<span class="tao_text_field">Paused</span></label>';
                                    $html .= '<div style="margin-bottom: 1.5em;">[cs_gb name="rejoin-button"]</div>';
                                    //Still have access?
                                    if ($has_access) {
                                        //Check when the access will expire
                                        $expiration = $wpdb->get_row('SELECT date_format(expiration,"%a %D %M") AS edate  FROM ' . $wpdb->prefix . 'gcc_group_expire WHERE user_id = ' . $user->ID . ' AND group_id = 2');
                                        if ($expiration != null) {
                                            //Expiration notice
                                            $html .= '<label style="margin-bottom: 0.5em;" for="tao_profile_membership">Plan Details<br>';
                                            $html .= '<span class="tao_text_field">Expires ' . $expiration->edate . '</span>';
                                            $html .= '</label>';
                                        } else {
                                            $html .= '<label style="margin-bottom: 0.5em;" for="tao_profile_membership">Plan Details<br>';
                                            $html .= '<span class="tao_text_field">Lifetime</span>';
                                            $html .= '</label>';                                            
                                        }
                                    }
                                    $found_plan = true;
                                    break;
                                }
                            }                            
                        }
                    }
                }
                //Check if there's been a single payment?
                if ($found_plan == false && isset($m->purchases)) {
                    foreach ($m->purchases as $pur) {
                        if ($pur->item_type == 'product' && $pur->item_id = '1') {
                            //Plan type
                            $purchase_type = 'Lifetime';
                            $expiration = $wpdb->get_row('SELECT date_format(expiration,"%a %D %M") AS edate  FROM ' . $wpdb->prefix . 'gcc_group_expire WHERE user_id = ' . $user->ID . ' AND group_id = 2');
                            if ($expiration != null) {
                                $purchase_type = 'Single';
                            }
                            //No access at all 
                            if ($has_access == false) {
                                $html .= '<span class="tao_text_field">Paused</span></label>';
                            } else {
                                $html .= '<span class="tao_text_field">Active (' . $purchase_type . ')</span></label>';
                            }
                            //Plan Details - showing access
                            if ($expiration != null) {
                                $html .= '<label style="margin-bottom: 0.5em;" for="tao_profile_membership">Plan Details<br>';
                                $html .= '<span class="tao_text_field">Expires ' . $expiration->edate . '</span>';
                                $html .= '</label>';                            
                            }
                            break;
                        }
                    }
                }
                $html .= '</div>';
            } else {
                /*
                    Thrive has not returned any data to work with
                    so we must work with what we know about their
                    access.
                */
                //Begin block
                $html .= '<div id="tao_membership">';
                $html .= '<label style="margin-bottom: 0.5em;" for="tao_profile_membership">Membership<br>';
                //Paused Membership
                $html .= '[member group="cancelled" realm="internal"]';
                $html .= '<span class="tao_text_field">Paused</span></label>';
                $html .= '<div style="margin-bottom: 1.5em;">[cs_gb name="rejoin-button"]</div>';
                $html .= '[/member]';
                //Never joined
                $html .= '[non_member group="subscriber" except="cancelled" realm="internal"]';
                $html .= '<span class="tao_text_field">No plan</span></label>';
                $html .= '<div style="margin-bottom: 1.5em;">[cs_gb name="join-button"]</div>';
                $html .= '[/non_member]';
                //End of the block
                $html .= '</div>';
                //Check when the subscription will expire
                $expiration = $wpdb->get_row('SELECT date_format(expiration,"%a %D %M") AS edate  FROM ' . $wpdb->prefix . 'gcc_group_expire WHERE user_id = ' . $user->ID . ' AND group_id = 2');
                if ($expiration != null) {
                    //Expiration notice
                    $html .= '<label style="margin-bottom: 0.5em;" for="tao_profile_membership">Plan Details<br>';
                    $html .= '<span class="tao_text_field">Expires ' . $expiration->edate . '</span>';
                    $html .= '</label>';
                }
                $html .= '</div>';
            }
        }
        
        return do_shortcode($html);
    }
    add_shortcode('tao_membership', 'tao_membership');


    //Connect with Thrive and request subscriptions
    function tao_request_membership($user) {
        
        //Debugging only, forces a ThriveCart refresh
        //delete_transient('_taotc_' . $user->user_email);

        //MemerDeck integration via Thrive
        $membership = get_transient('_taotc_' . $user->user_email);
        if ($membership == '') {
            $default = get_option('mjs_gcc_defaults', array());
            $thrive_api_token = isset($default['thrive_api_token']) ? $default['thrive_api_token'] : false;
            if ($thrive_api_token != false) {
                $initial_email = get_user_meta($user->ID, '_thrivecart_email', true);
                if ($initial_email != '') {
                    //Load from the backup address
                    $membership = tao_get_tc($thrive_api_token, 'customer', 'email=' . urlencode($initial_email), 'POST');
                    if (isset($membership->error) && $initial_email != $user->user_email) {
                        $membership = tao_get_tc($thrive_api_token, 'customer', 'email=' . urlencode($user->user_email), 'POST');
                    }
                } else {
                    $membership = tao_get_tc($thrive_api_token, 'customer', 'email=' . urlencode($user->user_email), 'POST');
                }
                //Save the transient
                set_transient('_taotc_' . $user->user_email, $membership, 180); //Cache for 180 seconds
            }
        }
        return $membership;
    }

    //Cancel the last subscription
    function tao_pause() {
        global $wpdb;
        check_ajax_referer( 'tao_account', 'nonce' );
        $default = get_option('mjs_gcc_defaults', array());
        $thrive_api_token = isset($default['thrive_api_token']) ? $default['thrive_api_token'] : false;
        $result = array(
            'data' => '',
            'message' => 'SUCCESS',
            'error' => false
        );
        $user = wp_get_current_user();
        $message = isset($_POST['message']) ? stripslashes($_POST['message']) : ''; 
        //Get the latest active subscription
        delete_transient('_taotc_' . $user->user_email); //Flush cache
        $m = tao_request_membership($user);
        //Active subscription?
        $cancelled = false;
        if (isset($m->subscriptions)) {
            if (count($m->subscriptions) > 0) {
                foreach ($m->subscriptions as $sub) {
                    //Only interested in the main product
                    if ($sub->status == 'active' && $sub->item_type == 'product' && $sub->item_id = '1') {
                        //Cancel the subscription
                        $c = tao_get_tc($thrive_api_token, 'cancelSubscription', 'order_id=' . $sub->order_id . '&subscription_id=' . $sub->subscription_id, "POST");
                        if (isset($c->success) && $c->success = 1) {
                            $cancelled = true;
                            /*
                                MemberDeck does not support paying subscriptions cancelling
                                this needs more research as to why that's the case.
                                Also - we need the UX to update to cancelled immediately
                                so it warrants adding members to cancelled right now.
                            */
                            $has_group = $wpdb->get_row('SELECT * FROM ' . $wpdb->prefix . 'groups_user_group WHERE user_id = ' . $user->ID . ' AND group_id = ' . MEMBERDECK_CANCELLED_GROUP);
                            if ($has_group == null) {
                                //Add the user to the cancelled group
                                $wpdb->insert($wpdb->prefix . 'groups_user_group',
                                    array(
                                        'user_id' => $user->ID,
                                        'group_id' => MEMBERDECK_CANCELLED_GROUP
                                    ),
                                    array(
                                        '%d', '%d'
                                    )
                                );
                            }
                        }
                    }
                }
            }
        }
        //Send admin email
        if ($cancelled == true && $message != '') {
            //Email
            $headers[] = 'From: ' . get_bloginfo('name') . ' <' . get_bloginfo('admin_email') . '>';
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
            $html  = '<p>Hey admin,</p>';
            $html .= '<p>The customer ' . $user->user_email . ' just cancelled their subscription and left the following message.</p>';
            $html .= '<div style="padding-top: 20px; padding-bottom: 20px; border: 3px solid #397eb3; border-radius: 0.2em; padding: 1em;">' . $message . '</div>';
            $html .= '<p>All the best,</p><p><a href="' . site_url() . '">' . get_bloginfo('name') . '</a></p>';
            wp_mail( get_bloginfo('admin_email'), '[REASON] Subscription Cancelled', $html, $headers );
        }
        //check if cancelled
        if ($cancelled == false) {
            $result['message'] = 'FAIL';
        }
        delete_transient('_taotc_' . $user->user_email); //Flush cache again   
        echo json_encode($result);
        exit();
    }
    add_action('wp_ajax_tao_pause', 'tao_pause');

    //Connect with ThriveCart to get the data
    function tao_get_tc($thrive_api_token, $target, $postfields = '', $method = 'GET') {
        $authorization = "Authorization: Bearer " . $thrive_api_token;
        $curl = curl_init();
        $data = array(
            CURLOPT_URL => 'https://thrivecart.com/api/external/' . $target,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method,
            //CURLOPT_POSTFIELDS => 'email=faux-customer-616855039-78%40thrivecartfaux.com',
            CURLOPT_HTTPHEADER => array(
              $authorization,  
              'X-TC-Mode: ' . THRIVECART_API_MODE
            ),
        );
        if ($postfields != '') {
            $data[CURLOPT_POSTFIELDS] = $postfields;
        }
        curl_setopt_array($curl, $data);
        $response = curl_exec($curl);
        curl_close($curl);
        return json_decode($response);  
    }

    //Membership first time flag
    function tao_detect_user_added($user_id, $group_id) {
        if ($group_id == MEMBERDECK_SUBSCRIBED_GROUP) {
            update_user_meta($user_id, '_tao_first_time', 1);
        }  
    }
    add_action('gcc_user_added_to_group', 'tao_detect_user_added', 2, 10);
    add_action('memberdeck_user_added_to_group', 'tao_detect_user_added', 2, 10);
    
    //Remove first time flag
    function tao_detect_user_remove($user_id, $group_id) {
        if ($group_id == MEMBERDECK_SUBSCRIBED_GROUP) {
            delete_user_meta($user_id, '_tao_first_time');
        }
    }
    add_action('gcc_user_removed_from_group', 'tao_detect_user_remove', 2, 10);
    add_action('memberdeck_user_removed_from_group', 'tao_detect_user_remove', 2, 10);

    //Ajax to cancel the first time
    function tao_first_time_done() {
        check_ajax_referer( 'tao_player', 'nonce' );
        $user = wp_get_current_user();
        delete_user_meta($user->ID, '_tao_first_time');
        echo 'OK';
        exit();
    }
    add_action('wp_ajax_first_time_done', 'tao_first_time_done');
?>