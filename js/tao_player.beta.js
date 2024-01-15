document.addEventListener('DOMContentLoaded', function() {
    console.log('Player 1.0.6');
    
    window.vimeoPlayer = false;
    window.last_video_obj = false;
    window.new_video = false;
    window.last_point = false;

    function exIntID(id) {
        const regex = /\d+$/; // Regular expression to match an integer at the end of the string
        const match = id.match(regex); // Apply regex to the input id string
        if (match) {
            return parseInt(match[0], 10); // If there's a match, parse it to an integer and return it
        } else {
            return null; // If there's no match, return null
        }
    }
    function copyDataAttributes(sourceElem, targetElem) {
        // Iterate through the source element's attributes
        for (let i = 0; i < sourceElem.attributes.length; i++) {
            const attr = sourceElem.attributes[i];
            // Check if the attribute name starts with 'data-'
            if (attr.name.startsWith('data-')) {
                // Set the target element's attribute with the same name and value
                targetElem.setAttribute(attr.name, attr.value);
            }
        }
    }
    
    //Version 6.2 Patch
    const playChapterElements = document.querySelectorAll('.tao_play_this_chapter');
    playChapterElements.forEach((element) => {
        const sourceElem = document.querySelector('#tptc_' + exIntID(element.id));
        const targetElem = document.querySelector('#' + element.id);
        copyDataAttributes(sourceElem, targetElem);
    });

    //Download available
    const downloadModels = document.querySelectorAll('.tao_download_model');
    downloadModels.forEach(function(downloadModel) {
        sm_toggle(true, 'tao_download_model');
        const closeDownload = downloadModel.querySelector('.tao_close_download');
        closeDownload.addEventListener('click', function() {
            sm_toggle(false, 'tao_download_model');
        });
        const downloadBtnClose = downloadModel.querySelector('.tao_download_btn_close');
        downloadBtnClose.addEventListener('click', function() {
            sm_toggle(false, 'tao_download_model');
        });
    });

    function tao_show_video(obj) {
        tao_setup_escape();
        //Get the data from the object
        var vo = document.querySelector(obj);
        last_video_obj = obj;
        if (vo.classList.contains('tao_player_preview')) {
            //Do something about the view?
            tao_player_preview_show();
            tao_play_video(vo);
        } else if (vo.classList.contains('tao_player_sample')) {
            if (!vo.classList.contains('tao_teaser')) {
                //Turn on watch point
                tao_time_update = true;
                //Set the hex value in the viewer
                document.querySelector('#video_viewer').dataset.hex = vo.dataset.hex;
                document.querySelector('#video_viewer').dataset.watch = vo.dataset.watch;
            }
            sm_toggle(true, 'tao_sample_modal', function(){
                if (vo.classList.contains('tao_holding')) {
                    tao_play_video(vo, 'holding_viewer');
                } else {
                    tao_play_video(vo, 'video_viewer');
                }
            });
        }
    }

    function tao_play_video(vo, player_id) {
        new_video = true;
        last_point = 0;
        if (vimeoPlayer) {
            vimeoPlayer.destroy().then(function () {
                tao_start(vo, player_id);
            });
        } else {
            tao_start(vo, player_id);
        }
        // Scroll into view
        if (!document.querySelector('#video_viewer').classList.contains('public_viewer')) {
            if (document.querySelector('#video_viewer')) {
                var position = document.querySelector('#video_viewer').getBoundingClientRect();
                window.scrollTo({
                    top: position.top + window.scrollY,
                    behavior: 'smooth'
                });
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
        vimeoPlayer.on('loaded', function(){
            document.querySelector('#video_viewer').style.background = '#f8f3ec';
        });
        //Set the position
        var pos = vo.dataset.position;
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
        var hex = document.querySelector('#video_viewer').getAttribute('data-hex');
        if (hex != undefined && hex != '') {
            var cpos = tao_getCookie('tao_watch_' + hex);
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
            vimeoPlayer.on('timeupdate', function(data) {
                var hex = document.querySelector('#video_viewer').getAttribute('data-hex');
                if (hex != undefined && hex != '') {
                    //Playback location 
                    tao_setCookie('tao_watch_' + hex, data.seconds, 365);
                    //First time opening
                    if (new_video == true) {
                        last_point = Math.floor(data.seconds);
                        tao_update_position(data);
                        new_video = false;
                    } else {
                        //Update position every 5 seconds
                        var f = Math.floor(data.seconds);
                        if ((f % 5 == 0) && (last_point != f)) {
                            last_point = f;
                            tao_update_position(data);
                        }
                    }
                    var pct = Math.round((data.seconds / data.duration) * 100);
                    document.querySelector('#tao_bar2_' + hex).style.width = pct + '%';
                    document.querySelector('#tao_bar_' + hex).style.width = pct + '%';
                    if (pct == 100) {
                        document.querySelector('#tao_bar2_' + hex).style.borderBottomRightRadius = '5px';
                        document.querySelector('#tao_bar_' + hex).style.borderBottomRightRadius = '5px';
                    }
                    //Update the chapter's data-position attribute
                    var chapter = document.querySelector('.tao_play_this_chapter[data-hex="' + hex + '"]');
                    chapter.dataset.progress = pct;
                    chapter.dataset.position = Math.floor(data.seconds);
                    document.querySelector('#tao_play_anyhow_' + hex).dataset.position = Math.floor(data.seconds);
                }
            });
            vimeoPlayer.on('ended', function(){
                //Detemine what to do next
                var hex = document.querySelector('#video_viewer').getAttribute('data-hex');
                var next = false;
                for (var i = 0; i < tao_chapters.length; i++) {
                    if (tao_chapters[i].hex_ID == hex) {
                        next = i;
                    }
                }
                if (tao_chapters[next + 1] == undefined) {
                    //Show feedback modal
                    var permalink = document.querySelector('#tao_permalink').value;
                    var feed = tao_getCookie('tao-feedback-' + permalink);
                    if (feed == '') {
                        sm_toggle(true, 'tao_feedback_modal');
                    }
                } else {
                    //Set up autoplay modal with optional notification
                    if (tao_chapters[next].notice != '' && tao_chapters[next].notice.body != '' && tao_chapters[next].notice.title != '') {
                        //Display notice and hide autoplay heading
                        document.querySelector('.tao_auto_mode').style.display = 'none';
                        document.querySelector('.tao_notice_mode').style.display = 'block';
                        document.querySelector('#tao_notice_headline').innerHTML = tao_chapters[next].notice.title;
                        document.querySelector('#tao_notice_body').innerHTML = tao_chapters[next].notice.body;
                        //Open the modal
                        sm_toggle(true, 'tao_autoplay_modal');    
                    } else {
                        //Display autoplay heading & hide notice 
                        document.querySelector('.tao_auto_mode').style.display = 'block';
                        document.querySelector('.tao_notice_mode').style.display = 'none';
                        //Open the autoplay modal with a countdown
                        sm_toggle(true, 'tao_autoplay_modal', function() {
                            window.autoplay_counter = 8;
                            document.querySelector('#auto_count').innerHTML = autoplay_counter;
                            tao_autoplay_timer = setTimeout(tao_autoplay_interval, 1000);
                        });
                    }
                }
            });
        }
    }

    //Autoplay timer
    window.autoplay_counter = 8;
    function tao_autoplay_interval() {
        autoplay_counter--;
        var modal = document.querySelector('.tao_autoplay_modal');
        var id = modal.getAttribute('data-x-toggleable');
        var isModalOpen = window.xToggleGetState(id);
        if (autoplay_counter == 0) {
            //Play next video
            if (isModalOpen) {
                tao_play_next();
            }
            //Close modal
            window.xToggleUpdate(id, false);
        } else {
            //Update counter
            document.querySelector('#auto_count').innerHTML = autoplay_counter;
            if (isModalOpen) {
                tao_autoplay_timer = setTimeout(tao_autoplay_interval, 1000);
            }
        }
    }

    //Continue button
    document.querySelectorAll('.tao_advance_chapter').forEach(function(element) {
        element.addEventListener('click', function() {
            //Clear countdown and close modal
            sm_toggle(false, 'tao_autoplay_modal', function(){
                clearTimeout(tao_autoplay_timer);
                tao_play_next();
            });        
        });
    });

    function tao_play_next() {
        //Detemine what to do next
        var hex = document.querySelector('#video_viewer').getAttribute('data-hex');
        var next = false;
        for (var i = 0; i < tao_chapters.length; i++) {
            if (tao_chapters[i].hex_ID == hex) {
                next = i;
            }
        }
        if (tao_chapters[next + 1] != undefined) {
            tao_viewer_play_chapter(document.querySelector('#' + tao_chapters[next + 1].obj), true);
        }
    }

    //Update the position of the player
    function tao_update_position(status) {
        var completed = 0;
        //Mark as completed if less than 15 seconds left and watched more than 95%
        if ((status.duration - status.seconds < 15) && ((status.seconds / status.duration) * 100) > 95) {
            completed = 1;
        }
        var dwt = document.querySelector('#video_viewer').getAttribute('data-watch');
        if (dwt == undefined || dwt == 1) {
            var data = {
                url: tao_player.url,
                method: 'POST',
                data: {
                    action: 'tao_watch',
                    nonce: tao_player.nonce,
                    hex: document.querySelector('#video_viewer').getAttribute('data-hex'),
                    position: last_point,
                    completed: completed
                }
            };
            window.tao_ajaxHandler(data, function(res) {
                if (res.error == false) {
                    // Handle success response
                } else {
                    console.log(res);
                }
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
    document.querySelectorAll('.tao_player_go').forEach(function(element) {
        element.addEventListener('click', function() {
            document.querySelector('#video_viewer').style.background = '#0a0b09';
            tao_show_video(this);
        });
    });

    function tao_setup_escape() {
        document.addEventListener('keyup', function(e) {
            if (e.key === "Escape") {
                tao_close_modal('video_modal');            
            }
        });
        window.addEventListener('keyup', function(e) {
            if (e.key === "Escape") {
                tao_close_modal('video_modal');            
            }
        });      
    }
    
    //Prepare player for the main preview viewer
    function tao_player_preview_show() {
        document.querySelectorAll('.tao_streaming_soon').forEach(function(element) {
            element.style.display = 'none';
        });
        document.querySelector('.tao_player_preview').style.background = '#0a0b09';
        document.querySelector('.tao_player_preview .x-bg').style.display = 'none';
    }

    //Prepare modal and open
    function tao_show_sample() {
        //Open the modal
        setTimeout(function(){
            document.querySelector('#tao_sample_modal-anchor-toggle').click();
        }, 100);
    }

    //Close modal, stop playing
    document.querySelectorAll('.tao_close_sample').forEach(function(element) {
        element.addEventListener('click', function() {
            //Pause the video
            if (vimeoPlayer) {
                vimeoPlayer.pause();
            }
            sm_toggle(false, 'tao_sample_modal');
        });
    });

    //Play public chapter
    document.querySelectorAll('.tao_play_public').forEach(function(element) {
        element.addEventListener('click', function() {
            var c = document.querySelector('.tao_chapter_0');
            if (c != undefined) {
                c.click();
            }
        });
    });

    //Expert Modal
    document.querySelectorAll('.tao_expert_push').forEach(function(element) {
        element.addEventListener('click', function(e) {
            var id = this.dataset.expert;
            e.preventDefault();
            setTimeout(function() {
                document.querySelector('#expert_' + id + '-anchor-toggle').click();
            }, 100);
        });
    });

    //Confirm skip order play button
    document.querySelectorAll('.tao_play_anyhow_btn').forEach(function(element) {
        element.addEventListener('click', function() {
            var btn = this;
            var video = btn.dataset.video;
            sm_toggle(false, 'tao_order_modal_' + video, function() {
                document.querySelector('#tao_play_' + video).dataset.opened = 1;
                tao_viewer_play_chapter(btn);
            });
            return false;
        });
    });
    //Viewer specific
    document.querySelectorAll('.tao_play_this_chapter').forEach(function(element) {
        element.addEventListener('click', function() {
            if (vimeoPlayer) {
                vimeoPlayer.pause();
            }
            if ((this.dataset.available == 0) && (this.dataset.opened == 0)) {
                //Open the modal
                var ID = this.dataset.video;
                setTimeout(function() {
                    document.querySelector('#tao_order_modal_' + ID + '-anchor-toggle').click();
                }, 100);
            } else {
                tao_viewer_play_chapter(this);
            }
        });
    });

    //Play in the big viewer
    function tao_viewer_play_chapter(obj, start) {
        //Update the meta information    
        document.getElementById('tao_viewer_chapter').innerHTML = obj.dataset.chapter;
        document.getElementById('tao_viewer_description').innerHTML = obj.dataset.description;
        document.querySelector('#tao_viewer_title h1').innerHTML = obj.dataset.name;
        //Set the video
        document.getElementById('video_viewer').dataset.videoUrl = obj.dataset.vimeo;
        document.getElementById('video_viewer').dataset.hex = obj.dataset.hex;
        document.getElementById('video_viewer').dataset.position = obj.dataset.position;
        //Flip the cover to play
        document.getElementById('restrict_cover_' + obj.dataset.video).style.display = 'none';
        document.getElementById('play_cover_' + obj.dataset.video).style.display = 'block';
        //Position the scroller  
        var id = false;
        var st = false;
        if (obj.dataset.modal != undefined && obj.dataset.modal != '') {
            id = 'tao_play_' + obj.dataset.video;
        } else {
            id = obj.id;  
        }
        var st = document.getElementById(id + '_block').offsetTop;
        //Scroll
        document.querySelector('.tao_chapter_block').scrollTop = st;
        //Reset the current play position
        if (start == true || obj.dataset.progress == 100) {
            tao_setCookie('tao_watch_' + obj.dataset.hex, 0, 365);
        }
        tao_play_video(document.getElementById('video_viewer'));
    }

    //Scrolling
    function tao_chapter_height() {
        var taoMetaPanel = document.querySelector('.tao_meta_panel');
        if (taoMetaPanel) {
            if (window.innerWidth > 979) {
                var taoMainViewerCol = document.querySelector('.tao_main_viewer_col');
                var taoChapterCol = document.querySelector('.tao_chapter_col');
                var taoChapterBlock = document.querySelector('.tao_chapter_block');
                var h1 = taoMainViewerCol.offsetHeight;
                var h2 = taoChapterCol.offsetHeight;
                taoChapterBlock.style.maxHeight = (h1 - h2) + 'px';

                var taoChapterChild = document.querySelectorAll('.tao_chapter_child');
                var gap = 0;
                var gridHeight = 0;
                for (var i = 0; i < (taoChapterChild.length - 1); i++) {
                    gridHeight += taoChapterChild[i].offsetHeight;
                    gap += 20;
                }
                var taoChapterGrid = document.querySelector('.tao_chapter_grid');
                var cg = taoChapterGrid.offsetHeight;
                gridHeight += gap;
                var taoChapterContainer = document.querySelector('.tao_chapter_container');
                taoChapterContainer.style.minHeight = ((h1 - h2) + gridHeight) + 'px';
            } else {
                var taoChapterBlock = document.querySelector('.tao_chapter_block');
                var taoChapterContainer = document.querySelector('.tao_chapter_container');
                taoChapterBlock.style.maxHeight = 'inherit';
                taoChapterContainer.style.minHeight = 'inherit';
            }
        }
    }
    window.addEventListener('resize', function(){
        tao_chapter_height();
    });

    var chapterBlocks = document.querySelectorAll('.tao_chapter_block');
    chapterBlocks.forEach(function(block){
        tao_chapter_height();
    });

    //Autoplay the chapter
    var tao_time_update = false;
    var videoViewer = document.getElementById('video_viewer');
    if (videoViewer && !videoViewer.classList.contains('public_viewer')) {
        //Initialise time update
        tao_time_update = true;
        //Upate the position of the scroll
        var id = videoViewer.dataset.video;
        var st = document.getElementById('tao_play_' + id + '_block').offsetTop;
        //Scroll
        document.querySelector('.tao_chapter_block').scrollTop = st;
        //Launch the player        
        tao_play_video(videoViewer);
    }

    //Listing specific
    document.querySelectorAll('.tao_play_chapter').forEach(function(element) {
        element.addEventListener('click', function() {
            if (element.dataset.available == 0 && element.dataset.opened == 0) {
                //Open the modal
                var ID = element.dataset.video;
                setTimeout(function() {
                    document.querySelector('#tao_order_modal_' + ID + '-anchor-toggle').click();
                }, 100);
            } else {
                tao_open_player(element);
            }
        });
    });

    document.querySelectorAll('.tao_advance_cancel').forEach(function(element) {
        element.addEventListener('click', function() {
            sm_toggle(false, 'tao_autoplay_modal', function() {
                clearTimeout(tao_autoplay_timer);
            });
        });
    });

    document.querySelectorAll('.tao_cancel_btn').forEach(function(element) {
        element.addEventListener('click', function() {
            var video = element.dataset.video;
            sm_toggle(false, 'tao_order_modal_' + video);
        });
    });
    document.querySelectorAll('.tao_nothanks_btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            sm_toggle(false, 'tao_continue_modal');
            //Set dismissed session cookie
            tao_setCookie('tao-cw-' + btn.dataset.permalink, 1, 0);
        });
    });

    function tao_open_player(obj) {
        var pt = obj.dataset.parent;
        var hex = obj.dataset.hex;
        window.location.href = 'https://thriveasone.ca/viewer/' + pt + '?tao=' + hex;
    }

    //Initialise progress bar length
    document.querySelectorAll('.tao_progress_bar, .tao_progress_bar_2').forEach(function(progressBar) {
        var dbpos = progressBar.dataset.progress;
        if (dbpos != undefined && dbpos != '') {
            progressBar.style.width = dbpos + '%';
        } else {
            var hex = progressBar.dataset.hex;
            if (hex != undefined && hex != '') {
                var cpos = tao_getCookie('tao_watch_' + hex);
                if (!isNaN(parseFloat(cpos))) {
                    pos = cpos;
                    //Calculate progress
                    var progress = (pos / progressBar.dataset.duration) * 100;
                    progressBar.style.width = progress + '%';
                    //Progress needs to be set on the later above
                    document.getElementById(progressBar.dataset.parent).dataset.position = progress;
                }
            }
        }
    });
    //Build an array of existing chapters
    window.tao_chapters = [];
    var taoPlayThisChapters = document.querySelectorAll('.tao_play_this_chapter');
    taoPlayThisChapters.forEach(function(taoPlayThisChapter) {
        tao_chapters.push({
            'hex_ID': taoPlayThisChapter.dataset.hex,
            'chapter': taoPlayThisChapter.dataset.chapter,
            'obj': 'tao_play_' + taoPlayThisChapter.dataset.video,
            'notice': (taoPlayThisChapter.dataset.notice != "") ? JSON.parse(atob(taoPlayThisChapter.dataset.notice)) : '',
        });
    });

    //Continue watching prompt?
    var taoOpenedVideo = document.querySelector('.tao_opened_video');
    if (taoOpenedVideo !== null) {
        //Session based dimissal cookie 
        var p = document.querySelector('.tao_nothanks_btn').dataset.permalink;
        var c = tao_getCookie('tao-cw-' + p);
        if (c != 1) {
            //Ask
            sm_toggle(true, 'tao_continue_modal');
        }
    }

    //Control star view
    document.querySelectorAll('.star').forEach(function(star) {
        star.addEventListener('mouseenter', function() {
            var rating = star.dataset.rating;
            document.querySelectorAll('.star').forEach(function(s) {
                var starNumber = s.dataset.star;
                if (starNumber <= rating) {
                    s.classList.add('star_on');
                    s.classList.remove('star_off');
                } else {
                    s.classList.remove('star_on');
                    s.classList.add('star_off');
                }
            });
        });

        star.addEventListener('mouseleave', function() {
            var rating = tao_get_feedback_rating();
            document.querySelectorAll('.star').forEach(function(s) {
                var starNumber = s.dataset.star;
                if (starNumber <= rating) {
                    s.classList.add('star_on');
                    s.classList.remove('star_off');
                } else {
                    s.classList.remove('star_on');
                    s.classList.add('star_off');
                }
            });
        });
    });
    function tao_get_feedback_rating() {
        var stars = document.querySelectorAll('.star');
        var rating = 0;
        for (var i = 0; i < stars.length; i++) {
            if (stars[i].dataset.selected != undefined && stars[i].dataset.selected == "true") {
                rating = stars[i].dataset.rating;
            }
        }
        return rating;
    }

    var starElements = document.querySelectorAll('.star');
    starElements.forEach(function(starElement) {
        starElement.addEventListener('click', function() {
            starElements.forEach(function(star) {
                star.dataset.selected = "";
            });
            this.dataset.selected = "true";
        });
    });

    var submitDoneButton = document.querySelector('.tao_submit_done');
    submitDoneButton.addEventListener('click', function() {
        sm_toggle(false, 'tao_feedback_modal');
    });

    document.querySelector('.tao_submit_feedback').addEventListener('click', function() {
        var data = {
            url: tao_player.url,
            method: 'POST',
            data: {
                action: 'tao_feedback',
                nonce: tao_player.nonce,
                rating: tao_get_feedback_rating(),
                permalink: document.querySelector('#tao_permalink').value,
                message: document.querySelector('#tao_feedback_message').value
            }
        };
        window.tao_ajaxHandler(data, function(res) {
            if (res.error == false) {
                // Success
                document.querySelector('#tao_feedback_ask').style.display = 'none';
                document.querySelector('#tao_feedback_confirm').style.display = 'block';
                var permalink = document.querySelector('#tao_permalink').value;
                // Set cookie to prevent reopening
                tao_setCookie('tao-feedback-' + permalink, 1, 365);
            } else {
                console.log(res);
            }
        });
    });
});