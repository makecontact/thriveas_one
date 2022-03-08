<?php
/*


MAKE ALL TAXONOMIES INVISIBLE BEFORE GOING LIVE :)


*/


    //Register main CPT
    function tao_register_cpt() {
        /*
            Register the custom post type that handles programs
            NOTE: Editing takes place in the CMS and the menu
            is visible to allow SEO meta data to be set. Editing
            the title will have no effect here - the permalink
            is set using the CMS.
        */

        //Used to managed to view of a program
        $labels = array(
            'name'                  => _x( 'TAO Viewer', 'Post type general name', 'tao' ),
            'singular_name'         => _x( 'Viewer', 'Post type singular name', 'tao' ),
            'menu_name'             => _x( 'Viewer', 'Admin Menu text', 'tao' ),
            'name_admin_bar'        => _x( 'Viewer', 'Add New on Toolbar', 'tao' ),
            'add_new'               => __( 'Add New', 'tao' ),
            'add_new_item'          => __( 'Add New program', 'tao' ),
            'new_item'              => __( 'New program', 'tao' ),
            'edit_item'             => __( 'Edit program', 'tao' ),
            'view_item'             => __( 'View program', 'tao' ),
            'all_items'             => __( 'All programs', 'tao' ),
            'search_items'          => __( 'Search programs', 'tao' ),
            'parent_item_colon'     => __( 'Parent programs:', 'tao' ),
            'not_found'             => __( 'No program found.', 'tao' ),
            'not_found_in_trash'    => __( 'No programs found in Trash.', 'tao' ),
            'featured_image'        => _x( 'Program Cover Image', 'Overrides the “Featured Image” phrase for this post type. Added in 4.3', 'tao' ),
            'set_featured_image'    => _x( 'Set cover image', 'Overrides the “Set featured image” phrase for this post type. Added in 4.3', 'tao' ),
            'remove_featured_image' => _x( 'Remove cover image', 'Overrides the “Remove featured image” phrase for this post type. Added in 4.3', 'tao' ),
            'use_featured_image'    => _x( 'Use as cover image', 'Overrides the “Use as featured image” phrase for this post type. Added in 4.3', 'tao' ),
            'archives'              => _x( 'Program archives', 'The post type archive label used in nav menus. Default “Post Archives”. Added in 4.4', 'tao' ),
            'insert_into_item'      => _x( 'Insert into program', 'Overrides the “Insert into post”/”Insert into page” phrase (used when inserting media into a post). Added in 4.4', 'tao' ),
            'uploaded_to_this_item' => _x( 'Uploaded to this program', 'Overrides the “Uploaded to this post”/”Uploaded to this page” phrase (used when viewing media attached to a post). Added in 4.4', 'tao' ),
            'filter_items_list'     => _x( 'Filter program list', 'Screen reader text for the filter links heading on the post type listing screen. Default “Filter posts list”/”Filter pages list”. Added in 4.4', 'tao' ),
            'items_list_navigation' => _x( 'Program list navigation', 'Screen reader text for the pagination heading on the post type listing screen. Default “Posts list navigation”/”Pages list navigation”. Added in 4.4', 'tao' ),
            'items_list'            => _x( 'Program list', 'Screen reader text for the items list heading on the post type listing screen. Default “Posts list”/”Pages list”. Added in 4.4', 'tao' ),
        );     
        $args = array(
            'labels'             => $labels,
            'description'        => 'TAO Viewer',
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array( 'slug' => 'viewer' ),
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => 20,
            'supports'           => array('title'),
            'taxonomies'         => array('tao_view_features'),
            'show_in_rest'       => false
        );     
        register_post_type( 'viewer', $args );

        //Add the taxonomy
        $labels = array(
            'name'                       => _x( 'Features', 'taxonomy general name', 'tao' ),
            'singular_name'              => _x( 'Feature', 'taxonomy singular name', 'tao' ),
            'search_items'               => __( 'Search Features', 'tao' ),
            'popular_items'              => __( 'Popular Features', 'tao' ),
            'all_items'                  => __( 'All Features', 'tao' ),
            'parent_item'                => null,
            'parent_item_colon'          => null,
            'edit_item'                  => __( 'Edit Feature', 'tao' ),
            'update_item'                => __( 'Update Feature', 'tao' ),
            'add_new_item'               => __( 'Add New Feature', 'tao' ),
            'new_item_name'              => __( 'New Feature Name', 'tao' ),
            'separate_items_with_commas' => __( 'Separate features with commas', 'tao' ),
            'add_or_remove_items'        => __( 'Add or remove features', 'tao' ),
            'choose_from_most_used'      => __( 'Choose from the most used features', 'tao' ),
            'not_found'                  => __( 'No features found.', 'tao' ),
            'menu_name'                  => __( 'Features', 'tao' ),
        );
     
        $args = array(
            'hierarchical'          => false,
            'labels'                => $labels,
            'publicly_queryable'    => true,
            'show_ui'               => true,
            'show_in_rest'          => true,
            'show_admin_column'     => false,
            'update_count_callback' => '_update_post_term_count',
            'query_var'             => true,
            'rewrite'               => false
        );
        register_taxonomy( 'tao_view_features', 'viewer', $args );  

        //Used to promote a program
        $labels = array(
            'name'                  => _x( 'TAO Programs', 'Post type general name', 'tao' ),
            'singular_name'         => _x( 'Program', 'Post type singular name', 'tao' ),
            'menu_name'             => _x( 'Programs', 'Admin Menu text', 'tao' ),
            'name_admin_bar'        => _x( 'Programs', 'Add New on Toolbar', 'tao' ),
            'add_new'               => __( 'Add New', 'tao' ),
            'add_new_item'          => __( 'Add New program', 'tao' ),
            'new_item'              => __( 'New program', 'tao' ),
            'edit_item'             => __( 'Edit program', 'tao' ),
            'view_item'             => __( 'View program', 'tao' ),
            'all_items'             => __( 'All programs', 'tao' ),
            'search_items'          => __( 'Search programs', 'tao' ),
            'parent_item_colon'     => __( 'Parent programs:', 'tao' ),
            'not_found'             => __( 'No program found.', 'tao' ),
            'not_found_in_trash'    => __( 'No programs found in Trash.', 'tao' ),
            'featured_image'        => _x( 'Program Cover Image', 'Overrides the “Featured Image” phrase for this post type. Added in 4.3', 'tao' ),
            'set_featured_image'    => _x( 'Set cover image', 'Overrides the “Set featured image” phrase for this post type. Added in 4.3', 'tao' ),
            'remove_featured_image' => _x( 'Remove cover image', 'Overrides the “Remove featured image” phrase for this post type. Added in 4.3', 'tao' ),
            'use_featured_image'    => _x( 'Use as cover image', 'Overrides the “Use as featured image” phrase for this post type. Added in 4.3', 'tao' ),
            'archives'              => _x( 'Program archives', 'The post type archive label used in nav menus. Default “Post Archives”. Added in 4.4', 'tao' ),
            'insert_into_item'      => _x( 'Insert into program', 'Overrides the “Insert into post”/”Insert into page” phrase (used when inserting media into a post). Added in 4.4', 'tao' ),
            'uploaded_to_this_item' => _x( 'Uploaded to this program', 'Overrides the “Uploaded to this post”/”Uploaded to this page” phrase (used when viewing media attached to a post). Added in 4.4', 'tao' ),
            'filter_items_list'     => _x( 'Filter program list', 'Screen reader text for the filter links heading on the post type listing screen. Default “Filter posts list”/”Filter pages list”. Added in 4.4', 'tao' ),
            'items_list_navigation' => _x( 'Program list navigation', 'Screen reader text for the pagination heading on the post type listing screen. Default “Posts list navigation”/”Pages list navigation”. Added in 4.4', 'tao' ),
            'items_list'            => _x( 'Program list', 'Screen reader text for the items list heading on the post type listing screen. Default “Posts list”/”Pages list”. Added in 4.4', 'tao' ),
        );     
        $args = array(
            'labels'             => $labels,
            'description'        => 'TAO Programs',
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array( 'slug' => 'program' ),
            'capability_type'    => 'post',
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => 20,
            'supports'           => array('title'),
            'taxonomies'         => array( 'tao_features','tao_categories'),
            'show_in_rest'       => false
        );     
        register_post_type( 'programs', $args );
        
        //Add the taxonomy
        $labels = array(
            'name'                       => _x( 'Features', 'taxonomy general name', 'tao' ),
            'singular_name'              => _x( 'Feature', 'taxonomy singular name', 'tao' ),
            'search_items'               => __( 'Search Features', 'tao' ),
            'popular_items'              => __( 'Popular Features', 'tao' ),
            'all_items'                  => __( 'All Features', 'tao' ),
            'parent_item'                => null,
            'parent_item_colon'          => null,
            'edit_item'                  => __( 'Edit Feature', 'tao' ),
            'update_item'                => __( 'Update Feature', 'tao' ),
            'add_new_item'               => __( 'Add New Feature', 'tao' ),
            'new_item_name'              => __( 'New Feature Name', 'tao' ),
            'separate_items_with_commas' => __( 'Separate features with commas', 'tao' ),
            'add_or_remove_items'        => __( 'Add or remove features', 'tao' ),
            'choose_from_most_used'      => __( 'Choose from the most used features', 'tao' ),
            'not_found'                  => __( 'No features found.', 'tao' ),
            'menu_name'                  => __( 'Features', 'tao' ),
        );
     
        $args = array(
            'hierarchical'          => false,
            'labels'                => $labels,
            'publicly_queryable'    => true,
            'show_ui'               => true,
            'show_in_rest'          => true,
            'show_admin_column'     => false,
            'update_count_callback' => '_update_post_term_count',
            'query_var'             => true,
            'rewrite'               => false
        );
        register_taxonomy( 'tao_features', 'programs', $args );        

        //Program categories
        $labels = array(
            'name'                       => _x( 'Categories', 'taxonomy general name', 'tao' ),
            'singular_name'              => _x( 'Category', 'taxonomy singular name', 'tao' ),
            'search_items'               => __( 'Search Categories', 'tao' ),
            'popular_items'              => __( 'Popular Categories', 'tao' ),
            'all_items'                  => __( 'All Categories', 'tao' ),
            'parent_item'                => null,
            'parent_item_colon'          => null,
            'edit_item'                  => __( 'Edit Category', 'tao' ),
            'update_item'                => __( 'Update Category', 'tao' ),
            'add_new_item'               => __( 'Add New Category', 'tao' ),
            'new_item_name'              => __( 'New Category Name', 'tao' ),
            'separate_items_with_commas' => __( 'Separate categories with commas', 'tao' ),
            'add_or_remove_items'        => __( 'Add or remove categories', 'tao' ),
            'choose_from_most_used'      => __( 'Choose from the most used categories', 'tao' ),
            'not_found'                  => __( 'No features found.', 'tao' ),
            'menu_name'                  => __( 'Categories', 'tao' ),
        );
        $args = array(
            'hierarchical'          => true,
            'labels'                => $labels,
            'publicly_queryable'    => true,
            'show_ui'               => false,
            'show_in_rest'          => true,
            'show_admin_column'     => false,
            'update_count_callback' => '_update_post_term_count',
            'query_var'             => true,
            'rewrite'               => false
        );
        register_taxonomy( 'tao_categories', 'programs', $args ); 
        
        //flush_rewrite_rules
    }
    add_action('init', 'tao_register_cpt');

    

?>