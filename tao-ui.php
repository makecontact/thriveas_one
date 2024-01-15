<?php

    //Disable jQuery from the build (Although, as of 14 Jan 2024 theme.co was still bundling it)
    add_filter('cs_use_jquery_everywhere', '__return_false');

    //Query vars for TAO
    function tao_query_vars( $qvars ) {
        $qvars[] = 'tao';
        return $qvars;
    }
    add_filter( 'query_vars', 'tao_query_vars' );


    function tao_player_embed($atts) {
        $production = true;
        if (WP_DEBUG == true) {
            $user_id = get_current_user_id();
            if (in_array($user_id, TAO_DEVELOPERS)) {
                $production = false;
            }
        }
        //Queue up libraries
        wp_enqueue_script( 'tao_vimeo', 'https://player.vimeo.com/api/player.js', '', '1.0.0', false);
        if ($production) {
            wp_enqueue_script( 'tao_player', plugin_dir_url(__FILE__) . 'js/tao_player.js', array('toaglobal', 'tao_vimeo'), TAO_PLAYER_LIB, false);
        } else {
            //Force it load with every request in debug mode
            $vid = wp_rand(0, 2147483647);
            wp_enqueue_script( 'tao_player', plugin_dir_url(__FILE__) . 'js/tao_player.beta.js', array('toaglobal', 'tao_vimeo'), TAO_PLAYER_LIB . $vid, false);
        }
        //Output div tag
        return '<!-- TAO Player -->';
    }
    add_shortcode('tao_player', 'tao_player_embed');


    //Coming soon filter
    function tao_cs_coming_soon($result, $params, $latest = false) {
        global $taodb;
        global $wpdb;
        global $post;
        if ($taodb == null) $taodb = tao_set_db();
        
        //Locate the request
        $sql = 'SELECT p.*, DATE_FORMAT(p.golive, "%b %Y") AS streaming, DATE_FORMAT(p.golive, "%M %Y") AS full_streaming, c.name AS category
        FROM tao_program AS p
        LEFT JOIN tao_program_category AS pc ON pc.program_ID = p.ID
        LEFT JOIN tao_category AS c ON pc.category_ID = c.ID
        WHERE p.status = 1
        ORDER BY p.published DESC, p.golive DESC';
        //Load based on the latest item
        if ($latest == true) {
            $sql = 'SELECT p.*, DATE_FORMAT(p.golive, "%b %Y") AS streaming, DATE_FORMAT(p.golive, "%M %Y") AS full_streaming, c.name AS category
            FROM tao_program AS p
            LEFT JOIN tao_program_category AS pc ON pc.program_ID = p.ID
            LEFT JOIN tao_category AS c ON pc.category_ID = c.ID
            WHERE p.status = 1
            ORDER BY p.promote DESC LIMIT 1';
        } else if (isset($params['permalink'])) {
            //Used on the program's homepage
            $sql = 'SELECT p.*, DATE_FORMAT(p.golive, "%b %Y") AS streaming, DATE_FORMAT(p.golive, "%M %Y") AS full_streaming, c.name AS category
            FROM tao_program AS p
            LEFT JOIN tao_program_category AS pc ON pc.program_ID = p.ID
            LEFT JOIN tao_category AS c ON pc.category_ID = c.ID
            WHERE p.status = 1
            AND p.post_title = "' . $post->post_name . '"
            ORDER BY p.published DESC, p.golive DESC';
        }
        //Load the program
        $program = $taodb->get_row($sql);
        if ($program != null) {
            //Load teaser video AKA '0' if it's published
            $video = $taodb->get_row(
                $wpdb->prepare( 
                    "SELECT v.vimeo, v.cover FROM tao_program_video AS pv
                     LEFT JOIN tao_video AS v ON pv.video_ID = v.ID
                     WHERE pv.program_ID = %d 
                     AND pv.chapter = 0
                     AND v.status = 1",
                    $program->ID
                )
            );
            //Attach the video vimeo code
            $meta = json_decode($program->meta, true);
            $p = array(
                'ID' => $program->ID,
                'title' => $program->title,
                'permalink' => $program->post_title,
                'description' => $meta['description'],
                'download' => isset($meta['download']) ? $meta['download'] : '', 
                'category' => $program->category,
                'streaming' => $program->streaming,
                'full_streaming' => $program->full_streaming,
                'vimeo' => '',
                'teaser' => 0,
                'hide_title' => 0
            );
            //Landscape photo
            $p = tao_expand_image_meta($p, 'landscape', $meta['landscape']);
            //Backups
            $vimeo = '';
            //Attach photo as a backup
            $p = tao_expand_image_meta($p, 'cover', $meta['landscape']);
            if ($video != null) {
                //Attach the HTML vimeo Code for this video
                $p['vimeo'] = $video->vimeo;
                //Use the video's cover instead
                $p = tao_expand_image_meta($p, 'cover', $video->cover);
                $p['teaser'] = 1;
            }
            //Episode or Documentary Button
            switch ($program->prog_type) {
                case 1:
                case 3:
                    $p['launch_button'] = 'Episode 1';
                    break;
                case 2:
                    $p['launch_button'] = 'Documentary';
                    break;
                default:
                    $p['launch_button'] = 'Episode 1';
                    break;
            }
            //Experts name
            if (isset($params['experts']) && $params['experts'] == true) {
                //Load associated experts
                $sql = 'SELECT c.* 
                FROM tao_program AS p
                LEFT JOIN tao_program_counsellor AS pc ON pc.program_ID = p.ID
                LEFT JOIN tao_counsellor AS c ON c.ID = pc.counsellor_ID
                WHERE p.ID = "' . $program->ID . '" AND pc.expert = 1 AND c.status = 1 ORDER BY pc.ID';
                $experts = $taodb->get_results($sql, ARRAY_A); 
                $expert_name = TAO_DEFAULT_EXPERT;
                $e = array();
                foreach ($experts as $expert) {
                    array_push($e, $expert['name']);
                }
                //Create title from the list
                $expert_name = implode(', ', $e);
                //Replace the final , with &amp;             
                $expert_name = strrev(implode(strrev(' &amp; '), explode(strrev(', '), strrev($expert_name), 2)));
                //Return the composite name
                $p['expert_name'] = $expert_name;
                //Overide the title if set
                if ($meta['sub_title'] != '') $p['expert_name'] = $meta['sub_title'];
                //Show the main title instead and flag to hide the title
                if ($p['expert_name'] == '') {
                    $p['expert_name'] = $p['title'];
                    $p['hide_title'] = 1;
                }
            }
            //Watch record
            if (is_user_logged_in()) {
                $user = wp_get_current_user();
                //Find a watch record for this program
                $sql = 'SELECT tw.hex_ID, tw.position
                FROM tao_program AS tp
                LEFT JOIN tao_program_video AS tpv ON tpv.program_ID = tp.ID
                LEFT JOIN tao_video AS tv ON tpv.video_ID = tv.ID
                LEFT JOIN tao_watch AS tw ON tw.hex_ID = tv.hex_ID
                WHERE tp.ID = ' . $program->ID . ' 
                AND tw.user_ID = ' . $user->ID . ' LIMIT 1'; 
                $watched =  $taodb->get_row($sql);
                if ($watched == null) {
                    $p['watched'] = 0;
                } else {
                    $p['watched'] = 1;
                }
            } 
            //Build the data for the screen
            array_push($result, $p);
        }
        return $result;
    }
    add_filter( 'cs_looper_custom_coming_soon', 'tao_cs_coming_soon', 10, 2);

    //Counsellors that are experts
    function tao_cs_experts($result, $param) {
        return tao_cs_counsellors_wrapped($result, $param, true);
    }
    add_filter('cs_looper_custom_experts', 'tao_cs_experts', 10, 2);

    //Counsellor that are not experts
    function tao_cs_counsellors($result, $param) {
        return tao_cs_counsellors_wrapped($result, $param, false);
    }
    add_filter('cs_looper_custom_counsellors', 'tao_cs_counsellors', 10, 2);

    //Wrapped function for counsellors
    function tao_cs_counsellors_wrapped($results, $params, $expert = false) {
        global $taodb;
        global $wpdb;
        global $post;
        if ($taodb == null) $taodb = tao_set_db();
        //Experts filter
        $expert_value = 0;
        if ($expert == true) {
            $expert_value = 1;
        }
        $experts = array();
        if ($post != null && property_exists($post, 'post_name')) {
            //Load the experts based on the epage
            $sql = 'SELECT c.* 
            FROM tao_program AS p
            LEFT JOIN tao_program_counsellor AS pc ON pc.program_ID = p.ID
            LEFT JOIN tao_counsellor AS c ON c.ID = pc.counsellor_ID
            WHERE p.post_title = "' . $post->post_name . '" AND pc.expert = ' . $expert_value  . ' AND c.status = 1 ORDER BY c.ID';
            $experts = $taodb->get_results($sql, ARRAY_A);
            //Merge meta into the experts data
            for ($i=0; $i < count($experts); $i++) {
                $meta = json_decode($experts[$i]['meta'], true);
                $experts[$i] = array_merge($experts[$i], $meta);
                $experts[$i] = tao_expand_image_meta($experts[$i], 'portrait', $meta['portrait']);
                $experts[$i] = tao_expand_image_meta($experts[$i], 'landscape', $meta['landscape']);
                unset($experts[$i]['meta']);
                unset($experts[$i]['portrait']);
                unset($experts[$i]['landscape']);
                //Preformat specialities
                if (isset($experts[$i]['spec']) && $experts[$i]['spec'] != '') {
                    $specs = explode(',', $experts[$i]['spec']);
                    $s = '<ul>';
                    foreach ($specs as $spec) {
                        $s .= '<li>' . trim($spec) . '</li>';
                    }
                    $s .= '</ul>';
                    //Set the html
                    $experts[$i]['spec'] = $s;
                }
            }
        }
        return $experts;
    }

    //Events
    function tao_events_helper($result, $param, $workshop = false) {
        global $taodb;
        global $wpdb;
        global $post;
        if ($taodb == null) $taodb = tao_set_db();

        //Type of the dataset being returned
        $event_type = 1;
        if ($workshop) $event_type = 0;

        //Grab the events of a specific type for this program
        $sql = 'SELECT te.ID, DATE_FORMAT(te.event_date,"%M %D, %Y") AS edate, te.meta 
        FROM tao_event AS te
        LEFT JOIN tao_program_event AS tpe ON tpe.event_ID = te.ID
        LEFT JOIN tao_program AS tp ON tpe.program_ID = tp.ID
        WHERE tp.post_title = "' . $post->post_name . '"
        AND te.event_type = ' . $event_type;
        $events = $taodb->get_results($sql, ARRAY_A);    

        //Add the meta data into the query
        for($i=0; $i < count($events); $i++) {
            $meta = json_decode($events[$i]['meta'], true);
            $events[$i] = array_merge($events[$i], $meta);
            $events[$i] = tao_expand_image_meta($events[$i], 'cover', $meta['cover']);
            unset($events[$i]['cover']);
            //Process the slider
            $events[$i]['has_slider'] = 0;
            if (isset($events[$i]['slider_one']) && $events[$i]['slider_one'] != '' && $events[$i]['slider_one'] != 'undefined') {
                $events[$i]['has_slider'] = 1; //We have a slider
                $slider = array();
                //Slides to look for
                $slides = array('one', 'two', 'three');    
                foreach ($slides as $s) {
                    if ($events[$i]['slider_' . $s] != '' && $events[$i]['slider_' . $s] != 'undefined') {
                        
                        $slide = array();
                        $slide = tao_expand_image_meta($slide, 'image', $events[$i]['slider_' . $s]);
                        
                        array_push($slider, $slide);       
                    }
                }
                $events[$i]['slider'] = $slider;
            }

            //Process experts
            if (!empty($events[$i]['experts'])) {
                $events[$i]['expert_list'] = $events[$i]['experts'];
                $events[$i]['experts'] = 1; //Has experts
                //Track a list of active experts returned by the DB
                $active_experts = array();
                for ($k =0; $k < count($events[$i]['expert_list']); $k++) {
                    //Load the expert data from counsellor
                    $expert = $taodb->get_row('SELECT * FROM tao_counsellor WHERE status = 1 AND ID = ' . $events[$i]['expert_list'][$k]['ID']);
                    if ($expert != null) {
                        $meta = json_decode($expert->meta, true);
                        $e = array(
                            'ID' => $expert->ID,
                            'title' => $expert->name,
                            'job' => $expert->job
                        );
                        $e = tao_expand_image_meta($e, 'portrait', $meta['portrait']);
                        array_push($active_experts, $e);
                    }
                }
                $events[$i]['expert_list'] = $active_experts;
                //Disable experts if all were disabled
                if (count($active_experts) == 0) $events[$i]['experts'] = 0;
            } else {
                $events[$i]['experts'] = 0; //Does not have experts
                $events[$i]['expert_list'] = array();
            }
            //Tidy up the data
            unset($events[$i]['meta']);
            unset($events[$i]['slider_one']);
            unset($events[$i]['slider_two']);
            unset($events[$i]['slider_three']); 
        }
        return $events;
    }
    //Retreats
    function tao_retreats($result, $param) {
        //Get retreats without experts
        $result = tao_events_helper($result, $param, false);
        return $result; 
    }
    add_filter('cs_looper_custom_retreats', 'tao_retreats', 10, 2);
    
    //Workshops
    function tao_workshops($result, $param) {
        //Get workshops without experts
        return tao_events_helper($result, $param, true);
    }
    add_filter('cs_looper_custom_workshops', 'tao_workshops', 10, 2);

    //Load available and coming soon programs
    function tao_cs_programs($result, $param) {
        global $taodb;
        global $wpdb;
        global $post;
        
        //Control which params are returned in the search
        $exclude_cats = isset($param['exclude']) ? $param['exclude'] : array();
        $include_cats = isset($param['categories']) ? $param['categories'] : array();
        $include_type = isset($param['type']) ? $param['type'] : array();
        $override = isset($param['override']) ? $param['override'] : array();
        $override_links = isset($param['override_links']) ? $param['override_links'] : array();
        if ($taodb == null) $taodb = tao_set_db();
        //Limit
        $limit = '';
        if (isset($param) && is_array($param) && isset($param['limit'])) {
            $limit = ' LIMIT ' . $param['limit'];
        }
        //Load Categories and get their slugs
        $categories = get_terms( 'tao_categories', array(
            'hide_empty' => false,
        ));
        //Filter by include_cats
        $filter = '';
        if (!empty($include_cats)) {
            $filter = ' AND c.ID IN (' . implode(',', $include_cats) . ') ';
        }
        if (!empty($include_type)) {
            $filter = ' AND p.prog_type IN (' . implode(',', $include_type) . ') ';
        }
        if (!empty($exclude_cats)) {
            $filter = ' AND c.ID NOT IN (' . implode(',', $exclude_cats) . ') ';
        }
        //Run search
        if (empty($override)) {
            $sql = 'SELECT p.*, DATE_FORMAT(p.golive, "%b %Y") AS streaming, DATE_FORMAT(p.golive, "%M %Y") AS full_streaming, c.name AS category
                    FROM tao_program AS p
                    LEFT JOIN tao_program_category AS pc ON pc.program_ID = p.ID
                    LEFT JOIN tao_category AS c ON pc.category_ID = c.ID
                    WHERE p.status = 1 ' . $filter . '
                    ORDER BY p.published DESC, p.golive ASC ' . $limit;
        } else {
            $sql = 'SELECT p.*, DATE_FORMAT(p.golive, "%b %Y") AS streaming, DATE_FORMAT(p.golive, "%M %Y") AS full_streaming, c.name AS category
            FROM tao_program AS p
            LEFT JOIN tao_program_category AS pc ON pc.program_ID = p.ID
            LEFT JOIN tao_category AS c ON pc.category_ID = c.ID
            WHERE p.ID IN (' . implode(',', $override) . ') 
            ORDER BY FIELD(p.ID,' . implode(',', $override) . ')';
        }
        $programs = $taodb->get_results($sql, ARRAY_A);
        if (count($programs) != 0) {
            for ($i=0; $i < count($programs); $i++) {
                //Add classname data for this entry
                $classes = array();
                //Hide after 6
                if ($i > 5) {
                    array_push($classes, 'tao_filter_hidden');
                }
                //Live or coming soon
                if ($programs[$i]['published'] == 1) {
                    array_push($classes, 'tao_filter_live');
                } else {
                    array_push($classes, 'tao_filter_soon');
                }
                //Category match
                $cat_slug = '';
                //Search cats
                foreach ($categories as $c) {
                    if ($c->name == $programs[$i]['category']) {
                        $cat_slug = 'tao_filter_cat_' . $c->slug;
                    }
                }
                if ($cat_slug != '') array_push($classes, $cat_slug);
                //Add the classes
                $programs[$i]['css_classes'] = implode(' ', $classes);
                //Expose the photos
                $meta = json_decode($programs[$i]['meta'], true);
                $programs[$i] = tao_expand_image_meta($programs[$i], 'portrait', $meta['portrait']);
                $programs[$i] = tao_expand_image_meta($programs[$i], 'landscape', $meta['landscape']);                
                $programs[$i]['short'] = $meta['short'];
                //Program type
                switch ($programs[$i]['prog_type']) {
                    case 1:    
                        $programs[$i]['prog_type_name'] = 'Master Series';
                        break;
                    case 2:
                        $programs[$i]['prog_type_name'] = 'Documentary';
                        break;
                    case 3:
                        $programs[$i]['prog_type_name'] = 'Docu-Series';
                        break;                        
                    default:
                        $programs[$i]['prog_type_name'] = 'Series';
                        break;
                }
                //Override link
                $programs[$i]['override'] = '';
                if (!empty($override_links)) {
                    foreach ($override_links as $l) {
                        if (isset($l[$programs[$i]['ID']])) {
                            $programs[$i]['override'] = $l[$programs[$i]['ID']];
                        }
                    }
                }
                unset($programs[$i]['meta']);
                unset($programs[$i]['viewer_id']);
                unset($programs[$i]['remote_id']);
            }
        }
        return $programs ;
    }
    add_filter('cs_looper_custom_programs', 'tao_cs_programs', 10, 2);
    

    //Attach image meta to the output full, large, medium, small & thumbnail
    function tao_expand_image_meta($result, $prefix, $image) {
        $meta = json_decode($image, true);
        //Order of types returns a reasonable quality if missing
        $sizes = array('thumbnail', 'full','large','small', 'medium');
        $has_empty = false;
        $last_good_image = '';
        foreach ($sizes as $size) {
           if (isset($meta[$size])) {
                $result[$prefix . '-' . $size] = $meta[$size]['url'];
                $last_good_image = $meta[$size]['url'];;
           } else {
               //Empty
               $result[$prefix . '-' . $size] = '';
               $has_empty = true;
           }
        }
        //Avoid returning blanks
        if ($has_empty) {
            foreach ($sizes as $size) {
                if ($result[$prefix . '-' . $size] == '') $result[$prefix . '-' . $size] = $last_good_image;
            }
        }
        return $result;
    }

    //Get the public chapters
    function tao_public_chapter($result, $param) {
        global $taodb;
        global $wpdb;
        global $post;
        if ($taodb == null) $taodb = tao_set_db();
        //Force trailers only
        $trailer = isset($param['trailer']) ? true : false;
        //Load this program's public chapters video
        $sql = 'SELECT tv.*, tpv.chapter, tp.prog_type
                FROM tao_program AS tp
                LEFT JOIN tao_program_video AS tpv ON tpv.program_ID = tp.ID
                LEFT JOIN tao_video AS tv ON tv.ID = tpv.video_ID
                WHERE tp.post_title = "' . $post->post_name . '"
                AND tpv.chapter <> 0
                AND tv.status = 1
                AND tpv.public = 1
                ORDER BY tpv.chapter ASC';
        if ($trailer) {
            $sql = 'SELECT tv.*, tpv.chapter, tp.prog_type
            FROM tao_program AS tp
            LEFT JOIN tao_program_video AS tpv ON tpv.program_ID = tp.ID
            LEFT JOIN tao_video AS tv ON tv.ID = tpv.video_ID
            WHERE tp.post_title = "' . $post->post_name . '"
            AND tpv.chapter = -1
            AND tv.status = 1
            AND tpv.public = 1
            ORDER BY tpv.chapter ASC';            
        }
        $result = $taodb->get_results($sql, ARRAY_A);
        if ($result == null) {
            return array();
        } else {
            //Disable watch tracking for public
            $logged_in = 0;
            if (is_user_logged_in()) {
                $logged_in = 1;
            }
            //Convert duration into time
            for ($i=0; $i < count($result); $i++) {
                $result[$i]['watch'] = $logged_in;
                $result[$i]['sequence'] = $i;
                $result[$i] = tao_expand_image_meta($result[$i], 'cover', $result[$i]['cover']);
                $result[$i]['length'] = tao_duration_to_time($result[$i]['duration']);
                if ($result[$i]['chapter'] == -1) {
                    $result[$i]['chapter'] = 'Trailer';
                } else {
                    switch ($result[$i]['prog_type']) {
                        case 1: //Master Series
                            $result[$i]['chapter'] = 'Episode ' . $result[$i]['chapter'];
                            break;
                        case 2: //Documentary
                            $result[$i]['chapter'] = 'Documentary';
                            break;
                        case 3: //Docu-Series
                            $result[$i]['chapter'] = 'Episode ' . $result[$i]['chapter'];
                            break;
                        default:
                        $result[$i]['chapter'] = 'Episode ' . $result[$i]['chapter'];
                    } 
                }
                unset($result[$i]['cover']);
            }
            return $result;
        }
    }
    add_filter('cs_looper_custom_public_chapters', 'tao_public_chapter', 10, 2);


    //Get the chapters
    function tao_chapters($result, $param) {
        global $taodb;
        global $wpdb;
        global $post;
        global $wp;
        if ($taodb == null) $taodb = tao_set_db();
        //Get the Hex_ID of the video being played
        $tao = isset($wp->query_vars['tao']) ? sanitize_text_field($wp->query_vars['tao']) : false;
        //Should we load extended data
        $extended = false;
        if (is_array($param) && isset($param['extended'])) {
            $extended = true;
        }
        $private = '';
        if (is_array($param) && isset($param['private'])) {
            $private = ' AND tpv.public = 0 ';
        } 
        $public = '';       
        if (is_array($param) && isset($param['public'])) {
            $private = ' AND tpv.public = 1 ';
        }
        $trailer = ' AND tpv.chapter > 0 ';
        if (is_array($param) && isset($param['trailer'])) {
            $trailer = ' AND tpv.chapter <> 0 ';
        }          

        //Load this program's non intro/sample chapters
        $sql = '';
        if ($extended) {
            //Grab the current user
            $user = wp_get_current_user();
            //Load extended data
            $sql = 'SELECT tv.*, tpv.chapter, TO_BASE64(tpv.notice) AS notice, tp.prog_type, IFNULL(tw.position, 0) AS position, IFNULL(tw.completed, 0) AS completed
            FROM tao_program AS tp
            LEFT JOIN tao_program_video AS tpv ON tpv.program_ID = tp.ID
            LEFT JOIN tao_video AS tv ON tv.ID = tpv.video_ID
            LEFT JOIN tao_watch AS tw ON (tv.hex_ID = tw.hex_ID AND tw.user_ID = ' . $user->ID . ')
            WHERE tp.post_title = "' . $post->post_name . '"
            ' . $trailer . '
            AND tv.status = 1
            ' . $private . $public . '
            ORDER BY tpv.chapter ASC';
        } else {
            //Load standard locked data for public
            $sql = 'SELECT tv.*, tpv.chapter, tp.prog_type
            FROM tao_program AS tp
            LEFT JOIN tao_program_video AS tpv ON tpv.program_ID = tp.ID
            LEFT JOIN tao_video AS tv ON tv.ID = tpv.video_ID
            WHERE tp.post_title = "' . $post->post_name . '"
            ' . $trailer . '
            AND tv.status = 1
            ' . $private . $public . '
            ORDER BY tpv.chapter ASC';            
        }
        $result = $taodb->get_results($sql, ARRAY_A);
        $first_video = true; //First video is ALWAYS available
        $last_video_available = false;
        for ($i = 0; $i < count($result); $i++) {
            $result[$i]['length'] = tao_duration_to_time($result[$i]['duration']);
            if ($result[$i]['chapter'] == '-1') {
                $result[$i]['chapter'] = 'Trailer';
            } else {
                switch ($result[$i]['prog_type']) {
                    case 1: //Master Series
                        $result[$i]['chapter'] = 'Episode ' . $result[$i]['chapter'];
                        break;
                    case 2: //Documentary
                        $result[$i]['chapter'] = 'Documentary';
                        break;
                    case 3: //Docu-Series
                        $result[$i]['chapter'] = 'Episode ' . $result[$i]['chapter'];
                        break;
                    default:
                    $result[$i]['chapter'] = 'Episode ' . $result[$i]['chapter'];
                }
            }
            $result[$i]['parent'] = $post->post_name;
            $result[$i] = tao_expand_image_meta($result[$i], 'cover', $result[$i]['cover']);
            unset($result[$i]['cover']);

            //Add in extended data
            if ($extended) {
                //Is it available e.g. the "show in order" cover blocks viewing
                if ($first_video == true || $last_video_available == true) {
                    $result[$i]['available'] = 1; //Show with play button
                    $last_video_available = false;
                    $first_video = false;
                } else {
                    $result[$i]['available'] = 0; //Show with "play in order" screen
                }
                //requested for viewing now
                if ($tao == $result[$i]['hex_ID']) {
                    $result[$i]['available'] = 1;
                }
                //Has this video been opened
                if ($result[$i]['position'] != 0) {
                    $result[$i]['opened'] = 1; //Started watching this vide - show play button
                    $result[$i]['progress'] =  round(($result[$i]['position'] / $result[$i]['duration']) * 100);
                    //Bound data to 0 - 100 (just in case of DB data)
                    if ($result[$i]['progress'] > 100) $result[$i]['progress'] = 100;
                    if ($result[$i]['progress'] < 0) $result[$i]['progress'] = 0;
                } else {
                    $result[$i]['opened'] = 0;
                    $result[$i]['progress'] = 0;
                }
                //Completed - enable the next video
                if ($result[$i]['completed'] == 1) {
                    $last_video_available = true;
                }
            }
        }

        //tao_error($result);

        return $result;
    }
    add_filter('cs_looper_custom_chapters', 'tao_chapters', 10, 2);


    //View homepage
    function tao_cs_viewer($result, $param) {
        global $taodb;
        global $wpdb;
        global $post;
        global $wp;
        if ($taodb == null) $taodb = tao_set_db();      
        //Get the Hex_ID
        $tao = isset($wp->query_vars['tao']) ? sanitize_text_field($wp->query_vars['tao']) : false; 
        //Grab the current user
        $user = wp_get_current_user();
        $video = null;
        $sql = '';
        //Load the hex listed video
        if ($tao) {
            $sql = 'SELECT tp.title AS program_title, tp.meta AS program_meta, tp.post_title, tv.*, tpv.chapter, IFNULL(tw.position, 0) AS position, IFNULL(tw.completed, 0) AS completed
            FROM tao_program AS tp
            LEFT JOIN tao_program_video AS tpv ON tpv.program_ID = tp.ID
            LEFT JOIN tao_video AS tv ON tv.ID = tpv.video_ID
            LEFT JOIN tao_watch AS tw ON (tv.hex_ID = tw.hex_ID AND tw.user_ID = ' . $user->ID . ')
            WHERE tp.post_title = "' . $post->post_name . '"
            AND tv.hex_ID = "' . $tao . '"
            AND tv.status = 1';
            //Attemp to load
            $video = $taodb->get_row($sql, ARRAY_A);
        }
        //Return the farthest opened chapter OR chapter 1
        if ($video == null) {
            $sql = 'SELECT tp.title AS program_title, tp.meta AS program_meta, tp.post_title, tv.*, tpv.chapter, IFNULL(tw.position, 0) AS position, IFNULL(tw.completed, 0) AS completed
            FROM tao_program AS tp
            LEFT JOIN tao_program_video AS tpv ON tpv.program_ID = tp.ID
            LEFT JOIN tao_video AS tv ON tv.ID = tpv.video_ID
            LEFT JOIN tao_watch AS tw ON (tv.hex_ID = tw.hex_ID AND tw.user_ID = ' . $user->ID . ')
            WHERE tp.post_title = "' . $post->post_name . '"
            AND (tpv.chapter = 1 OR tw.position <> 0)
            AND tv.status = 1
            ORDER BY tpv.chapter DESC
            LIMIT 1';
            //Attemp to load
            $video = $taodb->get_row($sql, ARRAY_A);
        }
        //Build out the data before returning
        if ($video != null) {
            $user_id = get_current_user_id();
            //Program Total Duration
            $sql = 'SELECT SUM(tv.duration) AS total, COUNT(tv.ID) AS videos 
                    FROM tao_program AS tp
                    LEFT JOIN tao_program_video AS tpv ON tpv.program_ID = tp.ID
                    LEFT JOIN tao_video AS tv ON tpv.video_ID = tv.ID
                    WHERE tp.post_title = "' . $post->post_name . '"
                    AND tv.status = 1
                    AND tpv.chapter > 0';
            $total_time = $taodb->get_row($sql, ARRAY_A);
            if ($total_time != null) {
                $video['total_time'] = tao_duration_to_time($total_time['total']);
                if ($total_time['videos'] == 1) {
                    $video['total_videos'] = $total_time['videos'] . ' Video';
                } else {
                    $video['total_videos'] = $total_time['videos'] . ' Videos';
                }
            }
            //Program Description
            $meta = json_decode($video['program_meta'], true);
            $video['program_description'] = $meta['short'];
            $video['download'] = isset($meta['download']) ? $meta['download'] : '';
            $video['permalink'] = $video['post_title'];
            unset($video['program_meta']);
            //Determine the state of the download
            $video['has_downloaded'] = get_user_meta($user_id, '_memberdeck_dl_' . $meta['download'], true);
            $video['has_latest'] = get_user_meta($user_id, '_memberdeck_dl_latest_' . $meta['download'], true);
            //Add the video to the result
            array_push($result, $video);
        }
        return $video;
    }
    add_filter('cs_looper_custom_viewer', 'tao_cs_viewer', 10, 2);
    

    //Converts seconds into a formatted time
    function tao_duration_to_time($length) {
        $hours = 0;
        $minutes = 0;
        //Calculate best format
        $hours = floor($length / 3600);
        $seconds_left = $length - ($hours * 3600);
        $minutes = floor($seconds_left / 60);
        $seconds_left = $length - (($hours * 3600) + ($minutes * 60));
        //Over an hour
        if ($hours > 0) {
            $length = $hours . 'hr';
            if ($minutes != 0) {
                $length .= ' ' . $minutes . 'min';
            }
        } else if ($minutes > 0) {
            //Under an hour but over a minute
            if ($minutes > 5) {
                $length = $minutes . 'min';
            } else {
                if ($seconds_left != 0) {
                    $length = $minutes . 'min ' . $seconds_left . 'sec';
                } else {
                    $length = $minutes . 'min';
                }
            }
        } else {
            $length = $length . 'sec';
        }
        return $length;
    }

    //Get the first live program
    function tao_cs_first_program($result, $params) {
        //Grab the latest live program
        $result = tao_cs_coming_soon($result, $params, true);
        return $result;        
    }
    add_filter('cs_looper_custom_first_program', 'tao_cs_first_program', 10, 2);

    //Clean up TAO data 
    function tao_clean_up( $user_id ) {
        global $taodb;
        if ($taodb == null) $taodb = tao_set_db();
        //Remove watch records for this user
        $taodb->delete(
            'tao_watch',
            array(
                'user_ID' => $user_id
            ),
            array(
                '%d'
            )
        );
    }
    add_action( 'delete_user', 'tao_clean_up' );
    
    //When a new customer joins, onboard any of their watch cookies into the database
    function tao_watch_onboarding($user_id) {
        global $taodb;
        if ($taodb == null) $taodb = tao_set_db();
        //Process cookies for this user
        $cookies = array_keys($_COOKIE);
        foreach ($cookies as $cookie) {
            if (strpos( $cookie , 'tao_watch_' ) === 0) {
                $c = explode('tao_watch_', $cookie);
                if (isset($c[1])) {
                    $progress = intval($_COOKIE[$cookie]);
                    $hex_ID = sanitize_text_field($c[1]);
                    if ($progress != '' && $hex_ID != '') {
                        //Does this video exist?
                        $video = $taodb->get_row('SELECT * FROM tao_video WHERE hex_ID = "' . $hex_ID . '"');
                        if ($video != null) {
                            //Does this user have a watch record already?
                            $watch = $taodb->get_row('SELECT * FROM tao_watch WHERE user_ID = ' . $user_id . ' AND hex_ID="' . $hex_ID . '"');
                            if ($watch == null) {
                                //Work out if they have completed the video
                                $completed = 0;
                                //Have the watched the video? (Same formula found in tao_player.js)
                                if (($video->duration - $progress < 15) && (($progress / $video->duration) * 100) > 95) {
                                    $completed = 1;
                                }
                                //Create the watch record
                                $taodb->insert('tao_watch',
                                    array(
                                        'hex_ID' => $hex_ID,
                                        'user_ID' => $user_id,
                                        'lastUpdate' => current_time('mysql', 1),
                                        'position' => $progress,
                                        'completed' => $completed
                                    ),
                                    array('%s', '%d', '%s', '%d', '%d')
                                );
                            }
                        }
                    }
                }
            }
        }
    }
    add_action('memberdeck_onboarding','tao_watch_onboarding', 1, 10);


    //Track watching
    function tao_watch() {
        check_ajax_referer( 'tao_global', 'nonce' );
        global $taodb;
        if ($taodb == null) $taodb = tao_set_db();
        $user = wp_get_current_user();
        //Read in params
        $hex_ID = isset($_REQUEST['hex']) ? sanitize_text_field($_REQUEST['hex']) : '';
        $position = isset($_REQUEST['position']) ? intval($_REQUEST['position']) : 0;
        //Completed status of this video
        $completed = isset($_REQUEST['completed']) ? intval($_REQUEST['completed']) : 0;
        if ($completed != 0) $completed = 1;
        if ($hex_ID != '') {
            //Attempt to update the row
            $rows = 0;
            if ($completed == 1) {
                //Completed the video, update completed status only once
                $rows = $taodb->update(
                    'tao_watch',
                    array(
                        'hex_ID' => $hex_ID,
                        'user_ID' => $user->ID,
                        'lastUpdate' => current_time('mysql', 1),
                        'position' =>  $position,
                        'completed' => $completed
                    ),
                    array(
                        'hex_ID' => $hex_ID,
                        'user_ID' => $user->ID
                    ),
                    array('%s', '%d', '%s', '%d', '%d'),
                    array('%s', '%d')
                );
            } else {
                //Don't overwrite completed status on update
                $rows = $taodb->update(
                    'tao_watch',
                    array(
                        'hex_ID' => $hex_ID,
                        'user_ID' => $user->ID,
                        'lastUpdate' => current_time('mysql', 1),
                        'position' =>  $position
                    ),
                    array(
                        'hex_ID' => $hex_ID,
                        'user_ID' => $user->ID
                    ),
                    array('%s', '%d', '%s', '%d'),
                    array('%s', '%d')
                );                
            }
            //Insert, update failed
            if ($rows == 0) {
                $rows = $taodb->insert(
                    'tao_watch',
                    array(
                        'hex_ID' => $hex_ID,
                        'user_ID' => $user->ID,
                        'lastUpdate' => current_time('mysql', 1),
                        'position' =>  $position,
                        'completed' => $completed
                    ),
                    array('%s', '%d', '%s', '%d', '%d')
                );
            }
            echo json_encode(array(
                'error' => false,
            ));
            exit();
        } else {
            echo json_encode(array(
                'error' => true,
            ));
            exit();
        }
    }
    add_action('wp_ajax_tao_watch', 'tao_watch');

    //Handle the feedback
    function tao_feedback() {
        check_ajax_referer( 'tao_global', 'nonce' );
        global $taodb;
        $result = array(
            'error' => false,
            'message' => ''
        );
        if ($taodb == null) $taodb = tao_set_db();
        $user = wp_get_current_user();
        $meta = get_userdata($user->ID);
        //Read in the feedback
        $permalink = isset($_REQUEST['permalink']) ? sanitize_text_field($_REQUEST['permalink']) : '';
        $message = isset($_REQUEST['message']) ? stripslashes($_REQUEST['message']) : '';
        $rating = isset($_REQUEST['rating']) ? intval($_REQUEST['rating']) : 0;

        //Look up the title
        $program = $taodb->get_row('SELECT * FROM tao_program WHERE post_title = "' . $permalink. '"');
        if ($program != null) {
            //Shorten title
            $title = $program->title;
            if (strlen($title) >= 40) {
                $title = substr($title, 0, 10). " ... " . substr($title, -5);
            }
            //Star text
            $star = 'stars';
            if ($rating == 1) $star = 'star';
            //Email
            $headers[] = 'From: ' . get_bloginfo('name') . ' <' . TAO_FEEDBACK_EMAIL . '>';
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
            $html  = '<p>Hey Thrive,</p>';
            $html .= '<p>' . $meta->first_name . ' ('. $user->user_email . ') just left feedback for your program titled <strong>' . $program->title . '</strong>.</p>';
            $html .= '<div style="padding-top: 20px; padding-bottom: 20px; border: 3px solid #397eb3; border-radius: 0.2em; padding: 1em; font-size: 1.2em;">';
            $html .= '<p><strong>' . $rating . ' ' . $star . ' ';
            for ($i=0; $i < $rating; $i++) {
                $html .= '*';
            }
            $html .= '</strong></p>';            
            $html .= '<p>' . $message . '</p></div>';
            $html .= '<p>All the best,</p><p><a href="' . site_url() . '">' . get_bloginfo('name') . '</a></p>';
            //Deliver the email
            $r = wp_mail(TAO_FEEDBACK_EMAIL , '[Feedback] ' . $title, $html, $headers);
            $result = array(
                'error' => false,
                'message' => 'Sent'
            );
        } else {
            $result = array(
                'error' => true,
                'message' => 'Program unknown'
            );
        }
        //Return
        echo json_encode($result);
        exit();     
    }
    add_action('wp_ajax_tao_feedback', 'tao_feedback');

    //Connect to the cms database
    function tao_set_db() {
        $taodb = new wpdb(REMOTE_DB_USER, REMOTE_DB_PASS, REMOTE_DB_NAME, REMOTE_DB_HOST);
        $taodb->set_prefix('wp_');
        return $taodb;
    }
?>