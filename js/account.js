document.addEventListener('DOMContentLoaded', function() {
    console.log('TAO Account 1.0.0');

    document.getElementById('tao_edit_profile').addEventListener('click', function(){
        tao_render_profile();
    });

    Array.from(document.querySelectorAll('#tao_membership')).forEach(function(){
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
        document.getElementById('tao_profile_editable').innerHTML = html;
        //Set hook for save button
        document.getElementById('profile_save').addEventListener('click', function(){
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
        var badgeMessage = document.createElement('div');
        badgeMessage.className = 'tao-badge-messsage';
        p.parentNode.insertBefore(badgeMessage, p.nextSibling);
        p.addEventListener("input", () => {
            clearTimeout(timeout);
            timeout = setTimeout(() => tao_strength(p.value), 100);
            if(p.value.length !== 0){
                //Show badge
                badgeMessage.style.display = 'block';
            } else{
                //Hide badge
                badgeMessage.style.display = 'none';
            }
        });
    }
    window.tao_strength = function(p){
        var badgeMessage = document.querySelector('.tao-badge-messsage');
        if(tao_strong_pass.test(p)) {
            //Pass
            badgeMessage.innerHTML = '<span style="color: #6bc0b2; font-weight: 700">Great password</span>';
        } else if(tao_medium_pass.test(p)){
            //OK
            badgeMessage.innerHTML = '<span style="color: #397eb3">Medium, add number &amp; special character.</span>';
        } else{
            //Fail
            badgeMessage.innerHTML = '<span style="color: #f06d6d">Weak password, it\'s too easy to guess.</span>';
        }
    }

    //Show the original screeen
    function tao_redraw() {
        var html = '<div id="tao_profile_editable" class="tao_profile">';    
        //First name
        html += '<div id="tao_set_firstname">';
        html += '<label for="tao_profile_firstname">First Name<br>';
        html += '<span class="tao_text_field">' + document.getElementById('tao_profile_firstname').value + '</span>';
        html += '</label></div>';               
        //Last name
        html += '<div id="tao_set_lastname">';
        html += '<label for="tao_profile_lastname">Last Name<br>';
        html += '<span class="tao_text_field">' + document.getElementById('tao_profile_lastname').value + '</span>';
        html += '</label></div>';           
        //Email
        html += '<div id="tao_set_email">';
        html += '<label for="tao_profile_email">Email<br>';
        html += '<span class="tao_text_field">' + document.getElementById('tao_profile_email').value + '</span>';
        html += '</label></div>';
        //Password 
        html += '<div id="tao_set_password">';
        html += '<label for="tao_profile_password">Password<br>';
        html += '<span class="tao_text_field">*********</span>';
        html += '</label></div>';
        //Edit link
        html += '<div><a id="tao_edit_profile" class="tao_main_link">Edit Profile</a></div>';
        html += '</div>';
        document.getElementById('tao_profile_editable').innerHTML = html;
        //Hook
        document.getElementById('tao_edit_profile').addEventListener('click', function(){
            tao_render_profile();
        });
    }

    //Save the profile
    function tao_save_profile() {
        //Reset errors
        Array.from(document.querySelectorAll('.tao-input-error')).forEach(function(element){
            element.classList.remove('tao-input-error');
        });
        Array.from(document.querySelectorAll('.tao-input-messsage')).forEach(function(element){
            element.parentNode.removeChild(element);
        });
        Array.from(document.querySelectorAll('.tao-badge-messsage')).forEach(function(element){
            element.parentNode.removeChild(element);
        });
        var firstname = document.getElementById('tao_profile_firstname');
        var lastname = document.getElementById('tao_profile_lastname');
        var email = document.getElementById('tao_profile_email');
        //Validate the form
        var passed = true;
        validateInput(firstname, 'Please enter in your first name.');
        validateInput(lastname, 'Please enter in your last name.');
        validateInput(email, 'Please enter in your new email.');
        if (email.value != '') {
            //Format of the email address
            var re = /^(([^<>()[\]\.,;:\s@\"]+(\.[^<>()[\]\.,;:\s@\"]+)*)|(\".+\"))@(([^<>()[\]\.,;:\s@\"]+\.)+[^<>()[\]\.,;:\s@\"]{2,})$/i;
            if (!re.test(email.value)) {
                setError(email, 'Please check your email address.');
            }
        }
        //Password form validation
        var p_current = document.getElementById('tao_pwd_current');
        var p_new = document.getElementById('tao_pwd_new');
        var p_confirm = document.getElementById('tao_pwd_confirm');
        var pass = '';
        //Check passwords
        if (p_new.value != '') {  
            validateInput(p_current, 'What\'s your current password?.');
            pass = p_current.value;           
            validateInput(p_new, 'Please enter in a new password.');
            if (passed == true) {
                validateInput(p_confirm, 'Please confirm your new password.');
                //Conditional validation
                if (passed == true) {
                    if (p_confirm.value != p_new.value) {
                        setError(p_confirm, 'Your new password doesn\'t match.');            
                    }
                    //Basic password requirements
                    if (!tao_medium_pass.test(p_new.value)) {
                        setError(p_new, 'Too easy to guess, use a harder password.');              
                    }
                }
            }
        }
        if (passed) {
            var data = {
                url: tao_account.url,
                method: 'POST',
                data: {
                    action: 'tao_edit_profile',
                    nonce: tao_account.nonce,
                    firstname: firstname.value,
                    lastname: lastname.value,
                    email: email.value,
                    current: pass,
                    new: p_new.value
                }
            };
            window.tao_ajaxHandler(data, function(res) {
                if (res.error == false) {
                    switch (res.message) {
                        case 'OK':
                            //Redraw only
                            tao_redraw();
                            break;
                        case 'UPDATED':
                            //Update the main data
                            tao_account.firstname = firstname.value;
                            tao_account.lastname = lastname.value;
                            tao_account.email = email.value;
                            //Show the modal
                            setTimeout(function(){
                                document.getElementById('tao_confirm-anchor-toggle').click();
                            }, 100);
                            //Close the modal
                            setTimeout(function(){
                                var id = document.querySelector('.tao_confirm').getAttribute('data-x-toggleable');
                                var isModalOpen = window.xToggleGetState(id);
                                if (isModalOpen) {
                                    window.xToggleUpdate(id, false);
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
                            setError(p_current, 'Incorrect password.');
                            break;    
                    }
                }
            });          
        }
    }

    function validateInput(inputElement, errorMessage) {
        if (inputElement.value == '') {
            passed = false;
            inputElement.classList.add('tao-input-error');
            var message = document.createElement('div');
            message.className = 'tao-input-messsage';
            message.textContent = errorMessage;
            inputElement.parentNode.insertBefore(message, inputElement.nextSibling);
        }
    }

    function setError(inputElement, errorMessage) {
        inputElement.classList.add('tao-input-error');
        var message = document.createElement('div');
        message.className = 'tao-input-messsage';
        message.textContent = errorMessage;
        inputElement.parentNode.insertBefore(message, inputElement.nextSibling);
    }

    document.getElementById('tao_cancel').addEventListener('click', function(){
        if (this.getAttribute('data-expire') != '') {
            document.querySelector('.expiration_notice').style.display = 'block';
            document.getElementById('tao_expire_contract').innerHTML = this.getAttribute('data-expire');
        } else {
            document.querySelector('.expiration_notice').style.display = 'block';
        }
        setTimeout(function(){
            document.getElementById('tao_cancel_window-anchor-toggle').click();
        }, 100);
    });

    document.querySelector('.tao_cancel_modal').addEventListener('click', function(){
        var id = document.querySelector('.tao_cmod').getAttribute('data-x-toggleable');
        var isModalOpen = window.xToggleGetState(id);
        if (isModalOpen) {
            window.xToggleUpdate(id, false);
        }        
    });

    document.getElementById('tao_cancel_message').addEventListener('keydown', function(e){
        console.log(e);
    });

    document.querySelector('.tao_pause_btn').addEventListener('click', function(){

        //Glitch with the theme requires this :/
        var msg = '';
        var cancelMessages = document.querySelectorAll('.tao_cancel_message');
        cancelMessages.forEach(function(messageElement){
            if (messageElement.value != '') {
                msg = messageElement.value;
            }
        });
        var data = {
            url: tao_account.url,
            method: 'POST',
            data: {
                action: 'tao_pause',
                nonce: tao_account.nonce,
                message: msg
            }
        };

        window.tao_ajaxHandler(data, function(res) {
            if (res.error == false) {
                switch (res.message) {
                    case 'SUCCESS':
                        //Update subscription details
                        document.getElementById('tao_plan_details').style.display = 'block';
                        document.getElementById('tao_main_details').style.display = 'none';
                        //Show confirmation of cancellation modal
                        var id = document.querySelector('.tao_cmod').getAttribute('data-x-toggleable');
                        var isModalOpen = window.xToggleGetState(id);
                        if (isModalOpen) {
                            window.xToggleUpdate(id, false);
                        }
                        //Show confirmation message MODAL
                        document.querySelector('.tao_pause_notice').innerHTML = 'Your membership has been successfully paused';
                        setTimeout(function(){
                            document.getElementById('tao_pause_confirm-anchor-toggle').click();
                        }, 100);
                        //Close the modal
                        setTimeout(function(){
                            var id = document.querySelector('.tao_pause_confirm').getAttribute('data-x-toggleable');
                            var isModalOpen = window.xToggleGetState(id);
                            if (isModalOpen) {
                                window.xToggleUpdate(id, false);
                            }
                        }, 4000);
                        break;
                    case 'FAIL':
                        //Show confirmation of cancellation modal
                        var id = document.querySelector('.tao_cmod').getAttribute('data-x-toggleable');
                        var isModalOpen = window.xToggleGetState(id);
                        if (isModalOpen) {
                            window.xToggleUpdate(id, false);
                        }
                        //Show confirmation message MODAL
                        document.querySelector('.tao_pause_notice').innerHTML = 'Sorry, we were unable to pause your membership, please contact support and we\'ll assist you further.';
                        setTimeout(function(){
                            document.getElementById('tao_pause_confirm-anchor-toggle').click();
                        }, 100);
                        //Close the modal
                        setTimeout(function(){
                            var id = document.querySelector('.tao_pause_confirm').getAttribute('data-x-toggleable');
                            var isModalOpen = window.xToggleGetState(id);
                            if (isModalOpen) {
                                window.xToggleUpdate(id, false);
                            }
                        }, 8000);
                        break;
                }
            } else {
                console.log(res);
            }
        });
    });
});