jQuery(document).ready(function() {

    window.vimeoPlayer = false;
    window.last_video_obj = false;
    window.new_video = false;
    window.last_point = false;

    //Cookie helper functions
	window.tao_delete_cookie = function(cname) {
		document.cookie = cname + '=; domain=' + intercpt.pd + '; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/';
	}
	window.tao_set_cookie = function(cname, cvalue, exdays) {
		if (exdays != 0) {
			var d = new Date();
			d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
			var expires = "expires=" + d.toUTCString();
			document.cookie = cname + "=" + cvalue + "; " + expires + "; path=/";
		} else {
			//Session cookie
			document.cookie = cname + "=" + cvalue + "; path=/";
		}
	}
	window.tao_get_cookie = function(cname) {
		var name = cname + "=";
		var ca = document.cookie.split(';');
		for (var i = 0; i < ca.length; i++) {
			var c = ca[i];
			while (c.charAt(0) == ' ') c = c.substring(1);
			if (c.indexOf(name) == 0) return c.substring(name.length, c.length);
		}
		return "";
	}

    function tao_show_video(obj) {
        tao_setup_escape();
        //Get the data from the object
        var vo = jQuery(obj);
        last_video_obj = obj;
        if (vo.hasClass('tao_player_preview')) {
            //Do something about the view?
            tao_player_preview_show();
            tao_play_video(vo);
        } else if (vo.hasClass('tao_player_sample')) {
            if (!vo.hasClass('tao_teaser')) {
                //Turn on watch point
                tao_time_update = true;
                //Set the hex value in the viewer
                jQuery('#video_viewer').data('hex', vo.data('hex'));
                jQuery('#video_viewer').data('watch', vo.data('watch'));
            }
            tao_show_sample();
            tao_play_video(vo, 'video_viewer');
        }
    }

    function tao_play_video(vo, player_id) {
        new_video = true;
        last_point = 0;
        if (vimeoPlayer) { 
            vimeoPlayer.destroy().then(function(){
                tao_start(vo, player_id);
            });
        } else {
            tao_start(vo, player_id);
        }
        //Scroll into view
        if (!jQuery('#video_viewer').hasClass('public_viewer')) {
            if (jQuery('#video_viewer').length != 0) {
                var position = jQuery('#video_viewer').position();
                scroll(0,position.top);
            }
        }
    }

    function tao_start(vo, player_id) {
        //Create a new player
        var p = vo.prop('id');
        if (player_id != undefined && player_id != '') {
            p = player_id;
        } 
        //Load the video into the new player           
        vimeoPlayer = new Vimeo.Player(
            p, {
                url: vo.data('video-url'),
                width: vo.data('video-width'),
                'autoplay': true
            }
        );
        //Set the position
        var pos = jQuery(vo).data('position');
        if (pos != undefined && pos != '') {
            pos = tao_get_pos(pos);
            vimeoPlayer.setCurrentTime(pos).then(function() {
                // The video is playing
                tao_play();
            }).catch(function(error) {
                switch (error.name) {
                case 'PasswordError':
                    // The video is password-protected
                    console.log('password error');
                    break;
                case 'PrivacyError':
                    console.log('privacy error');
                    // The video is private
                    break;
            
                default:
                    console.log(error);
                    // Some other error occurred
                    break;
                }
            });                
        } else {
            tao_play();
        }
        //Kick off monitoring
        tao_playback(); 
    }

    function tao_get_pos(pos) {
        var hex = jQuery('#video_viewer').data('hex');
        if (hex != undefined && hex != '') {
            var cpos = tao_get_cookie('tao_watch_' + hex);
            if (parseFloat(cpos) != NaN) {
                pos = cpos;
            }
        }
        return pos;
    }
    
    //Keep track of the playback point
    window.tao_autoplay_timer = false;
    function tao_playback() {
        if (tao_time_update && vimeoPlayer) {
            vimeoPlayer.on('timeupdate', function(data){
                var hex = jQuery('#video_viewer').data('hex');
                if (hex != undefined && hex != '') {
                    //Playback location 
                    tao_set_cookie('tao_watch_' + hex, data.seconds, 365);
                    //First time opening
                    if (new_video == true) {
                        last_point = Math.floor(data.seconds);
                        tao_update_position(data);
                        new_video = false;
                    } else {
                        //Update position every 5 seconds
                        var f = Math.floor(data.seconds);
                        if ((f % 5 == 0)  && (last_point != f)) {
                            last_point = f;
                            tao_update_position(data);
                        }
                    }
                    var pct = Math.round((data.seconds / data.duration) * 100);
                    jQuery('#tao_bar2_' + hex).css('width', pct + '%');
                    jQuery('#tao_bar_' + hex).css('width', pct + '%');
                    if (pct == 100) {
                        jQuery('#tao_bar2_' + hex).css('border-bottom-right-radius', '5px');
                        jQuery('#tao_bar_' + hex).css('border-bottom-right-radius', '5px');
                    }
                    //Update the chapter's data-position attribute
                    var chapter = jQuery('.tao_play_this_chapter[data-hex="' + hex + '"]');
                    chapter.data('progress', pct);
                    chapter.data('position', Math.floor(data.seconds));
                    jQuery('#tao_play_anyhow_' + hex).data('position', Math.floor(data.seconds));
                }
            });
            vimeoPlayer.on('ended', function(){
                //Detemine what to do next
                var hex = jQuery('#video_viewer').data('hex');
                var next = false;
                for (var i = 0; i < tao_chapters.length; i++) {
                    if (tao_chapters[i].hex_ID == hex) {
                        next = i;
                    }
                }
                if (tao_chapters[next + 1] == undefined) {
                    //Show feedback modal
                    var permalink = jQuery('#tao_permalink').val();
                    var feed = tao_get_cookie('tao-feedback-' + permalink);
                    if (feed == '') {
                        var id = jQuery('.tao_feedback_modal').attr('data-x-toggleable');
                        var isModalOpen = window.xToggleGetState( id );
                        if (!isModalOpen) {
                            window.xToggleUpdate( id, true );
                        }
                    }
                } else {
                    //Open the autoplay modal
                    var id = jQuery('.tao_autoplay_modal').attr('data-x-toggleable');
                    var isModalOpen = window.xToggleGetState( id );  
                    if (!isModalOpen) { 
                        window.xToggleUpdate( id, true );
                    }
                    tao_autoplay_timer = setTimeout(tao_autoplay_interval, 1000);
                }
            });
        }
    }

    //Autoplay timer
    window.autoplay_counter = 8;
    function tao_autoplay_interval() {
        autoplay_counter--;
        var id = jQuery('.tao_autoplay_modal').attr('data-x-toggleable');
        var isModalOpen = window.xToggleGetState( id );        
        if (autoplay_counter == 0) {
            //Play next video
            if (isModalOpen) { 
                tao_play_next();
            }    
            //Close modal
            window.xToggleUpdate( id, false );
        } else {
            //Update counter
            jQuery('#auto_count').html(autoplay_counter);
            if (isModalOpen) {           
                tao_autoplay_timer = setTimeout(tao_autoplay_interval, 1000);
            }
        }
    }

    //Continue button
    jQuery('.tao_advance_chapter').on('click', function(){
        //Clear countdown and close modal
        var id = jQuery('.tao_autoplay_modal').attr('data-x-toggleable');
        var isModalOpen = window.xToggleGetState( id );
        if (isModalOpen) {
            window.xToggleUpdate( id, false );
        }
        clearTimeout(tao_autoplay_timer);
        tao_play_next();
    });

    function tao_play_next() {
        //Detemine what to do next
        var hex = jQuery('#video_viewer').data('hex');
        var next = false;
        for (var i = 0; i < tao_chapters.length; i++) {
            if (tao_chapters[i].hex_ID == hex) {
                next = i;
            }
        }
        if (tao_chapters[next + 1] != undefined) {
            tao_viewer_play_chapter(jQuery('#' + tao_chapters[next + 1].obj), true);
        }
    }

    //Update the position of the player
    function tao_update_position(status) {
        var completed = 0;
        //Mark as completed if less than 15 seconds left and watched more than 95%
        if ((status.duration - status.seconds < 15) && ((status.seconds / status.duration) * 100) > 95) {
            completed = 1;
        }
        var dwt = jQuery('#video_viewer').data('watch');
        if (dwt == undefined || dwt == 1) {
            jQuery.ajax(tao_player.url, {
                timeout: 60000,
                data: {
                    'action': 'tao_watch',
                    'nonce': tao_player.nonce,
                    'hex': jQuery('#video_viewer').data('hex'),
                    'position': last_point,
                    'completed': completed
                },
                type: "POST",
                success: function(response) {}
            });
        }
    }

    function tao_play() {
        //New player from zero
        vimeoPlayer.play().then(function() {
        // The video is playing
        }).catch(function(error) {
            switch (error.name) {
            case 'PasswordError':
                // The video is password-protected
                console.log('password error');
                break;       
            case 'PrivacyError':
                console.log('privacy error');
                // The video is private
                break;
            default:
                console.log(error);
                // Some other error occurred
                break;
            }
        });       
    }

    //Assign trigger to video players
    jQuery('.tao_player_go').on('click', function() {
        tao_show_video(this);
    });

    function tao_setup_escape() {
        jQuery(document).keyup(function(e) {
            if (e.key === "Escape") {
                tao_close_modal('video_modal');            
            }
        });
        jQuery(window).keyup(function(e) {
            if (e.key === "Escape") {
                tao_close_modal('video_modal');            
            }
        });      
    }
    
    //Prepare player for the main preview viewer
    function tao_player_preview_show() {
        jQuery('.tao_streaming_soon').hide();
        jQuery('.tao_player_preview').css('background','#0a0b09');
        jQuery('.tao_player_preview .x-bg').hide();
    }

    //Prepare modal and open
    function tao_show_sample() {
        //Open the modal
        setTimeout(function(){
            jQuery('#tao_sample_modal-anchor-toggle')[0].click();
        }, 100);
    }

    //Close modal, stop playing
    jQuery('.tao_close_sample').on('click', function(){
        var id = jQuery('.tao_sample_modal').attr('data-x-toggleable');
        var isModalOpen = window.xToggleGetState( id );
        if (isModalOpen) {
            window.xToggleUpdate( id, false );
        }
        //Pause the video
        if (vimeoPlayer) {
            vimeoPlayer.pause();
        }        
    });

    //Play public chapter
    jQuery('.tao_play_public').on('click', function(){
        var c = jQuery('.tao_chapter_0');
        if (c != undefined) {
            c.trigger('click');
        }
    });

    //Expert Modal
    jQuery('.tao_expert_push').on('click', function(e){
        var id = jQuery(this).data('expert');
        e.preventDefault();
        setTimeout(function(){
            jQuery('#expert_' + id + '-anchor-toggle')[0].click();
        }, 100);
    });

    //Confirm skip order play button
    jQuery('.tao_play_anyhow_btn').on('click', function(){
        var video = jQuery(this).data('video');
        var id = jQuery('.tao_order_modal_' + video).attr('data-x-toggleable');
        var isModalOpen = window.xToggleGetState( id );
        if (isModalOpen) {
          window.xToggleUpdate( id, false );
        }
        //Set it to opened
        jQuery('#tao_play_' + video).data('opened', 1);
        tao_viewer_play_chapter(this);
        return false;
    });
    //Viewer specific
    jQuery('.tao_play_this_chapter').on('click', function(){
        if (vimeoPlayer) {
            vimeoPlayer.pause();
        }        
        if ((jQuery(this).data('available') == 0) && (jQuery(this).data('opened') == 0)) {
            //Open the modal
            var ID = jQuery(this).data('video');
            setTimeout(function(){
                jQuery('#tao_order_modal_' + ID + '-anchor-toggle')[0].click();
            }, 100);
        } else {
            tao_viewer_play_chapter(this);
        }
    });

    //Play in the big viewer
    function tao_viewer_play_chapter(obj, start) {
        //Update the meta information    
        jQuery('#tao_viewer_chapter').html( jQuery(obj).data('chapter') );
        jQuery('#tao_viewer_description').html( jQuery(obj).data('description') );
        jQuery('#tao_viewer_title h1').html( jQuery(obj).data('name') );
        //Set the video
        jQuery('#video_viewer').data('video-url', jQuery(obj).data('vimeo') );
        jQuery('#video_viewer').data('hex', jQuery(obj).data('hex'));
        jQuery('#video_viewer').data('position', jQuery(obj).data('position'));
        //Flip the cover to play
        jQuery('#restrict_cover_' + jQuery(obj).data('video')).hide();
        jQuery('#play_cover_' + jQuery(obj).data('video')).show();  
        //Position the scroller  
        var id = false;
        var st = false;
        if (jQuery(obj).data('modal') != undefined && jQuery(obj).data('modal') != '') {
            id = 'tao_play_' + jQuery(obj).data('video');
        } else {
            id = jQuery(obj).attr('id');  
        }
        var st = jQuery('#' + id + '_block').position().top;
        //Scroll
        jQuery('.tao_chapter_block').animate({scrollTop: st});
        //Reset the current play position
        if (start == true || jQuery(obj).data('progress') == 100) {
            tao_set_cookie('tao_watch_' + jQuery(obj).data('hex'), 0, 365);
        }
        tao_play_video(jQuery('#video_viewer'));
    }

    //Scrolling
    function tao_chapter_height() {
        if (jQuery('.tao_meta_panel').length != 0) {
            if (jQuery(window).width() > 979) {
                //Set the height of the scroll area
                var h1 = jQuery('.tao_main_viewer_col').height(); 
                var h2 = jQuery('.tao_chapter_col').height();
                jQuery('.tao_chapter_block').css('max-height', (h1 - h2) + 'px');
                //Work out the grid scrollable height
                var grid_height = 0;
                var cgc = jQuery('.tao_chapter_child');
                var gap = 0;
                for (var i = 0; i < (cgc.length -1); i++) {
                    grid_height += jQuery(cgc[i]).height();
                    gap += 20;
                }
                var cg = jQuery('.tao_chapter_grid').height();
                grid_height += gap;
                jQuery('.tao_chapter_container').css('min-height', ((h1 - h2) + grid_height) + 'px');
            } else {
                jQuery('.tao_chapter_block').css('max-height', 'inherit');
                jQuery('.tao_chapter_container').css('min-height', 'inherit');
            }
        }
    }
    jQuery(window).on('resize', function(){
        tao_chapter_height();
    });
    jQuery('.tao_chapter_block').each(function(){
        tao_chapter_height();
    });
        
    //Autoplay the chapter
    window.tao_time_update = false;
    jQuery('#video_viewer').each(function(){
        if (!jQuery(this).hasClass('public_viewer')) {
            //Initialise time update
            tao_time_update = true;
            //Upate the position of the scroll
            var id = jQuery('#video_viewer').data('video');
            var st = jQuery('#tao_play_' + id + '_block').position().top;
            //Scroll
            jQuery('.tao_chapter_block').animate({scrollTop: st});
            //Launch the player        
            tao_play_video(jQuery('#video_viewer'));
        }        
    });

    //Listing specific
    jQuery('.tao_play_chapter').on('click', function(){
    	if ( (jQuery(this).data('available') == 0) && (jQuery(this).data('opened') == 0)) {
            //Open the modal
            var ID = jQuery(this).data('video');
            setTimeout(function(){
                jQuery('#tao_order_modal_' + ID + '-anchor-toggle')[0].click();
            }, 100);
        } else {
            tao_open_player(this);
        }
    });      
    jQuery('.tao_advance_cancel').on('click', function(){
        var id = jQuery('.tao_autoplay_modal').attr('data-x-toggleable');
        var isModalOpen = window.xToggleGetState( id );
        if (isModalOpen) {
            window.xToggleUpdate( id, false );
            clearTimeout(tao_autoplay_timer);
        }
    });   
    jQuery('.tao_cancel_btn').on('click', function(){
        var video = jQuery(this).data('video');
        var id = jQuery('.tao_order_modal_' + video).attr('data-x-toggleable');
        var isModalOpen = window.xToggleGetState( id );
        if (isModalOpen) {
            window.xToggleUpdate( id, false );
        }
    });
    jQuery('.tao_nothanks_btn').on('click', function(){
        var id = jQuery('.tao_continue_modal').attr('data-x-toggleable');
        var isModalOpen = window.xToggleGetState( id );
        if (isModalOpen) {
            window.xToggleUpdate( id, false );
        }
        //Set dismissed session cookie
        tao_set_cookie('tao-cw-' + jQuery(this).data('permalink'), 1, 0);
    });
    function tao_open_player(obj) {
        var pt = jQuery(obj).data('parent');
        var hex = jQuery(obj).data('hex');
        window.location.href = 'https://thriveasone.ca/viewer/' + pt + '?tao=' + hex;
    }
    //Initialise progress bar length
    jQuery('.tao_progress_bar, .tao_progress_bar_2').each(function(){
        var dbpos = jQuery(this).data('progress');
        if (dbpos != undefined && dbpos != '') {
            jQuery(this).css('width', dbpos + '%');
        } else {
            var hex = jQuery(this).data('hex');
            if (hex != undefined && hex != '') {
                var cpos = tao_get_cookie('tao_watch_' + hex);
                if (parseFloat(cpos) != NaN) {
                    pos = cpos;
                    //Calculate progress
                    var progress = (pos / jQuery(this).data('duration')) * 100;
                    jQuery(this).css('width', progress + '%');
                    //Progress needs to be set on the later above
                    jQuery( '#' + jQuery(this).data('parent') ).data('position', progress);
                }    
            }
        }
    });
    //Build an array of existing chapters
    window.tao_chapters = [];
    jQuery('.tao_play_this_chapter').each(function(){
        tao_chapters.push({
            'hex_ID': jQuery(this).data('hex'),
            'chapter': jQuery(this).data('chapter'),
            'obj': 'tao_play_' + jQuery(this).data('video') 
        });
    });

    //Continue watching prompt?
    if (jQuery('.tao_opened_video').length != 0) {
        //Session based dimissal cookie 
        var p = jQuery('.tao_nothanks_btn').data('permalink');
        var c = tao_get_cookie('tao-cw-' + p);
        if (c != 1) {
            //Ask
            var id = jQuery('.tao_continue_modal').attr('data-x-toggleable');
            var isModalOpen = window.xToggleGetState( id );
            if (!isModalOpen) {
                window.xToggleUpdate( id, true );
            }
        }
    }

    //Control star view
    jQuery('.star').hover(function(){
        //In
        var r = jQuery(this).data('rating');
        var s = jQuery('.star');
        for (var i=1; i <= 5; i++) {
            if (i <= r) {
                //on
                jQuery('.star_' + i).addClass('star_on');
                jQuery('.star_' + i).removeClass('star_off');
            } else {
                //off
                jQuery('.star_' + i).removeClass('star_on');
                jQuery('.star_' + i).addClass('star_off');                
            }
        }
    },
    function(){
        //Reset view
        var r = tao_get_feedback_rating();
        for (var i=1; i <= 5; i++) {
            if (i <= r) {
                //on
                jQuery('.star_' + i).addClass('star_on');
                jQuery('.star_' + i).removeClass('star_off');
            } else {
                //off
                jQuery('.star_' + i).removeClass('star_on');
                jQuery('.star_' + i).addClass('star_off');                
            }        
        }
    });
    function tao_get_feedback_rating() {
        var s = jQuery('.star');
        var r = 0;
        for (var i=0; i < s.length; i++) {
            if (jQuery(s[i]).data('selected') != undefined && jQuery(s[i]).data('selected') == true) {
                r = jQuery(s[i]).data('rating');
            }
        }
        return r;
    }

    jQuery('.star').click(function(){
        jQuery('.star').data('selected','');
        jQuery(this).data('selected', true);
    });
    jQuery('.tao_submit_done').on('click', function(){
        var id = jQuery('.tao_feedback_modal').attr('data-x-toggleable');
        var isModalOpen = window.xToggleGetState( id );
        if (isModalOpen) {
            window.xToggleUpdate( id, false );
        }
    });
    jQuery('.tao_submit_feedback').on('click', function(){
        jQuery.ajax(tao_player.url, {
            timeout: 60000,
            data: {
                'action': 'tao_feedback',
                'nonce': tao_player.nonce,
                'rating': tao_get_feedback_rating(),
                'permalink': jQuery('#tao_permalink').val(),
                'message': jQuery('#tao_feedback_message').val()
            },
            type: "POST",
            success: function(response) {},
            complete: function() {
                //Confirm
                jQuery('#tao_feedback_ask').hide();
                jQuery('#tao_feedback_confirm').show();
                var permalink = jQuery('#tao_permalink').val();
                //Set cookie to prevent reopening
                tao_set_cookie('tao-feedback-' + permalink, 1, 365);
            }
        });

    });
});