jQuery(document).ready(function() {
    console.log('TAO Account 1.0.0');

    jQuery('#tao_edit_profile').on('click', function(){
        tao_render_profile();
    });

    jQuery('#tao_membership').each(function(){
        //tao_request_status();
    });

    //Display the editable form
    function tao_render_profile() {
        //Render view for edit
        var html = '';
        //Firstname
        html += '<div id="tao_set_firstname">';
        html += '<label for="tao_profile_firstname">First Name<br>';
        html += '<input type="text" placeholder="First name" id="tao_profile_firstname" name="tao_profile_firstname" class="tao_input" value="' + tao_account.firstname + '">';
        html += '</label></div>';   
        //Lastname     
        html += '<div id="tao_set_lastname">';
        html += '<label for="tao_profile_firstname">Last Name<br>';
        html += '<input type="text" placeholder="Last name" id="tao_profile_lastname" name="tao_profile_lastname" class="tao_input" value="' + tao_account.lastname + '">';
        html += '</label></div>'; 
        //Email     
        html += '<div id="tao_set_email">';
        html += '<label for="tao_profile_firstname">Email<br>';
        html += '<input type="text" placeholder="Email" id="tao_profile_email" name="tao_profile_email" class="tao_input" value="' + tao_account.email + '">';
        html += '</label></div>';
        html += '<div class="tao_divide"></div>';
        //Password
        if (tao_account.ready != '') {
            //Existig password required
            html += '<div id="tao_set_pwd_current">';
            html += '<label for="tao_pwd_current">Current Password<br>';
            html += '<input type="password" placeholder="Current Password" id="tao_pwd_current" name="tao_pwd_current" class="tao_input">';
            html += '</label></div>';
            //New
            html += '<div id="tao_set_pwd_new">';
            html += '<label for="tao_pwd_current">New Password<br>';
            html += '<input type="password" placeholder="New Password" id="tao_pwd_new" name="tao_pwd_new" class="tao_input">';
            html += '</label></div>';             
        } else {
            //New
            html += '<div id="tao_set_pwd_new">';
            html += '<label for="tao_pwd_current">Set Password<br>';
            html += '<input type="password" placeholder="Set Password" id="tao_pwd_new" name="tao_pwd_new" class="tao_input">';
            html += '</label></div>'; 
        }
        //Confirm
        html += '<div id="tao_set_pwd_confirm">';
        html += '<label for="tao_pwd_confirm">Confirm Password<br>';
        html += '<input type="password" placeholder="Confirm Password" id="tao_pwd_confirm" name="tao_pwd_confirm" class="tao_input">';
        html += '</label></div>'; 
        //Save Button
        html += '<a id="profile_save" class="tao_btn">Save Profile</a>';
        //Set view
        jQuery('#tao_profile_editable').html(html);
        //Set hook for save button
        jQuery('#profile_save').on('click', function(){
            tao_save_profile();
        });
        //Weak password check
        tao_check_strength();
    }

    //Strength checker
    window.tao_strong_pass = new RegExp('(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9])(?=.{8,})');
    window.tao_medium_pass = new RegExp('(?=.*[a-zA-Z])(?=.*[0-9])(?=.{8,})');

    function tao_check_strength() {
        var p = document.getElementById('tao_pwd_new');
        var timeout;
        var password = document.getElementById('PassEntry');
        jQuery('#tao_pwd_new').after('<div class="tao-badge-messsage"></div>');
        p.addEventListener("input", () => {
            clearTimeout(timeout);
            timeout = setTimeout(() => tao_strength(p.value), 100);
            if(p.value.length !== 0){
                //Show badge
                jQuery('.tao-badge-messsage').show();
            } else{
                //Hide badge
                jQuery('.tao-badge-messsage').hide();
            }
        });
    }
    window.tao_strength = function(p){
        if(tao_strong_pass.test(p)) {
            //Pass
            jQuery('.tao-badge-messsage').html('<span style="color: #6bc0b2; font-weight: 700">Great password</span>');
        } else if(tao_medium_pass.test(p)){
            //OK
            jQuery('.tao-badge-messsage').html('<span style="color: #397eb3">Medium, add number &amp; special character.</span>');
        } else{
            //Fail
            jQuery('.tao-badge-messsage').html('<span style="color: #f06d6d">Weak password, it\'s too easy to guess.</span>');
        }
    }

    //Show the original screeen
    function tao_redraw() {
        var html = '<div id="tao_profile_editable" class="tao_profile">';    
        //First name
        html += '<div id="tao_set_firstname">';
        html += '<label for="tao_profile_firstname">First Name<br>';
        html += '<span class="tao_text_field">' + jQuery('#tao_profile_firstname').val() + '</span>';
        html += '</label></div>';               
        //Last name
        html += '<div id="tao_set_lastname">';
        html += '<label for="tao_profile_lastname">Last Name<br>';
        html += '<span class="tao_text_field">' + jQuery('#tao_profile_lastname').val() + '</span>';
        html += '</label></div>';           
        //Email
        html += '<div id="tao_set_email">';
        html += '<label for="tao_profile_email">Email<br>';
        html += '<span class="tao_text_field">' + jQuery('#tao_profile_email').val() + '</span>';
        html += '</label></div>';
        //Password 
        html += '<div id="tao_set_password">';
        html += '<label for="tao_profile_password">Password<br>';
        html += '<span class="tao_text_field">*********</span>';
        html += '</label></div>';
        //Edit link
        html += '<div><a id="tao_edit_profile" class="tao_main_link">Edit Profile</a></div>';
        html += '</div>';
        jQuery('#tao_profile_editable').html(html);
        //Hook
        jQuery('#tao_edit_profile').on('click', function(){
            tao_render_profile();
        });
    }

    //Save the profile
    function tao_save_profile() {
        //Reset errors
        jQuery('.tao-input-error').removeClass('tao-input-error');
        jQuery('.tao-input-messsage').remove();
        jQuery('.tao-badge-messsage').remove();
        var firstname = jQuery('#tao_profile_firstname');
        var lastname = jQuery('#tao_profile_lastname');
        var email = jQuery('#tao_profile_email');
        //Validate the form
        var passed = true;
        if (firstname.val() == '') {
            passed = false;
            firstname.addClass('tao-input-error');
            firstname.after('<div class="tao-input-messsage">Please enter in your first name.</div>');
        }
        if (lastname.val() == '') {
            passed = false;
            lastname.addClass('tao-input-error');
            lastname.after('<div class="tao-input-messsage">Please enter in your last name.</div>');
        }
        if (email.val() == '') {
            passed = false;
            email.addClass('tao-input-error');
            email.after('<div class="tao-input-messsage">Please enter in your new email.</div>');
        } else {
            //Format of the email address
            var re = /^(([^<>()[\]\.,;:\s@\"]+(\.[^<>()[\]\.,;:\s@\"]+)*)|(\".+\"))@(([^<>()[\]\.,;:\s@\"]+\.)+[^<>()[\]\.,;:\s@\"]{2,})$/i;
            if (!re.test(email.val())) {
                passed = false;
                email.addClass('tao-input-error');
                email.after('<div class="tao-input-messsage">Please check your email address.</div>');                
            }
        }
        //Password form validation
        var p_current = jQuery('#tao_pwd_current');
        var p_new = jQuery('#tao_pwd_new');
        var p_confirm = jQuery('#tao_pwd_confirm');
        var pass = '';
        //Check passwords
        if (p_new.val() != '') {  
            if (p_current.length != 0) {
                if (p_current.val() == '') {
                    passed = false;
                    p_current.addClass('tao-input-error');
                    p_current.after('<div class="tao-input-messsage">What\'s your current password?.</div>');
                } 
                pass = p_current.val();           
            }
            if (p_new.val() == '') {
                passed = false;
                p_new.addClass('tao-input-error');
                p_new.after('<div class="tao-input-messsage">Please enter in a new password.</div>');
            }
            if (passed == true && p_confirm.val() == '') {
                passed = false;
                p_confirm.addClass('tao-input-error');
                p_confirm.after('<div class="tao-input-messsage">Please confirm your new password.</div>');
            }
            //Conditional validation
            if (passed == true) {
                if (p_confirm.val() != p_new.val()) {
                    passed = false;
                    p_confirm.addClass('tao-input-error');
                    p_confirm.after('<div class="tao-input-messsage">Your new password doesn\'t match.</div>');             
                }
                //Basic password requirements
                if (!tao_medium_pass.test(p_new.val())) {
                    passed = false;
                    p_new.addClass('tao-input-error');
                    p_new.after('<div class="tao-input-messsage">Too easy to guess, use a harder password.</div>');               
                }
            }
        }
        if (passed) {
            jQuery.ajax(tao_account.url, {
                timeout: 60000,
                data: {
                    'action': 'tao_edit_profile',
                    'nonce': tao_account.nonce,
                    'firstname': firstname.val(),
                    'lastname': lastname.val(),
                    'email': email.val(),
                    'current': pass,
                    'new': p_new.val()
                },
                type: "POST",
                error: function() {						
                },
                success: function(response) {
                    //Process response
                    var res = JSON.parse(response);
                    if (res.error == false) {
                        switch (res.message) {
                            case 'OK':
                                //Redraw only
                                tao_redraw();
                                break;
                            case 'UPDATED':
                                //Update the main data
                                tao_account.firstname = firstname.val();
                                tao_account.lastname = lastname.val();
                                tao_account.email = email.val();
                                //Show the modal
                                setTimeout(function(){
                                    jQuery('#tao_confirm-anchor-toggle')[0].click();
                                }, 100);
                                //Close the modal
                                setTimeout(function(){
                                    var id = jQuery('.tao_confirm').attr('data-x-toggleable');
                                    var isModalOpen = window.xToggleGetState( id );
                                    if (isModalOpen) {
                                        window.xToggleUpdate( id, false );
                                    }
                                }, 4000);                               
                                //Redraw
                                tao_redraw();
                                break;   
                        }
                    } else {
                        switch (res.message) {
                            case 'PASSWORD':
                                //Set error message
                                p_current.addClass('tao-input-error');
                                p_current.after('<div class="tao-input-messsage">Incorrect password.</div>');
                                break;    
                        }
                    }
                }
            });            
        }
    }

    jQuery('#tao_cancel').on('click', function(){
        if (jQuery(this).data('expire') != '') {
            jQuery('.expiration_notice').show();
            jQuery('#tao_expire_contract').html(jQuery(this).data('expire'));
        } else {
            jQuery('.expiration_notice').show();
        }
        setTimeout(function(){
            jQuery('#tao_cancel_window-anchor-toggle')[0].click();
        }, 100);
    });
    jQuery('.tao_cancel_modal').on('click', function(){
        var id = jQuery('.tao_cmod').attr('data-x-toggleable');
        var isModalOpen = window.xToggleGetState( id );
        if (isModalOpen) {
            window.xToggleUpdate( id, false );
        }        
    });

    jQuery('#tao_cancel_message').on('keydown', function(e){
        console.log(e);
    });

    jQuery('.tao_pause_btn').on('click', function(){

        //Glitch with the theme requires this :/
        var msg = '';
        jQuery('.tao_cancel_message').each(function(){
            if (jQuery(this).val() != '') {
                msg = jQuery(this).val();
            }
        });
        jQuery.ajax(tao_account.url, {
            timeout: 60000,
            data: {
                'action': 'tao_pause',
                'nonce': tao_account.nonce,
                'message': msg,
            },
            type: "POST",
            error: function() {						
            },
            success: function(response) {
                var res = JSON.parse(response);
                if (res.error == false) {
                    switch (res.message) {
                        case 'SUCCESS':
                            //Update subscription details
                            jQuery('#tao_plan_details').show();
                            jQuery('#tao_main_details').hide();
                            //Show confirmation of cancellation modal
                            var id = jQuery('.tao_cmod').attr('data-x-toggleable');
                            var isModalOpen = window.xToggleGetState( id );
                            if (isModalOpen) {
                                window.xToggleUpdate( id, false );
                            }
                            //Show confirmation message MODAL
                            jQuery('.tao_pause_notice').html('Your membership has been successfully paused');
                            setTimeout(function(){
                                jQuery('#tao_pause_confirm-anchor-toggle')[0].click();
                            }, 100);
                            //Close the modal
                            setTimeout(function(){
                                var id = jQuery('.tao_pause_confirm').attr('data-x-toggleable');
                                var isModalOpen = window.xToggleGetState( id );
                                if (isModalOpen) {
                                    window.xToggleUpdate( id, false );
                                }
                            }, 4000);
                            break;
                        case 'FAIL':
                            //Show confirmation of cancellation modal
                            var id = jQuery('.tao_cmod').attr('data-x-toggleable');
                            var isModalOpen = window.xToggleGetState( id );
                            if (isModalOpen) {
                                window.xToggleUpdate( id, false );
                            }
                            //Show confirmation message MODAL
                            jQuery('.tao_pause_notice').html('Sorry, we were unable to pause your membership, please contact support and we\'ll assist you further.');
                            setTimeout(function(){
                                jQuery('#tao_pause_confirm-anchor-toggle')[0].click();
                            }, 100);
                            //Close the modal
                            setTimeout(function(){
                                var id = jQuery('.tao_pause_confirm').attr('data-x-toggleable');
                                var isModalOpen = window.xToggleGetState( id );
                                if (isModalOpen) {
                                    window.xToggleUpdate( id, false );
                                }
                            }, 8000);
                            break;
                    }
                } else {
                    console.log(res);
                }
            }
        });   
    });
});