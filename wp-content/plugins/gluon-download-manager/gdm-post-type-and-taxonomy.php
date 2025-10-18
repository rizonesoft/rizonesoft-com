<?php

function gdm_register_post_type() {

	//*****  Create 'gdm_downloads' Custom Post Type
	$labels = array(
		'name'               => __( 'Downloads', 'gluon-download-manager' ),
		'singular_name'      => __( 'Downloads', 'gluon-download-manager' ),
		'add_new'            => __( 'Add New', 'gluon-download-manager' ),
		'add_new_item'       => __( 'Add New', 'gluon-download-manager' ),
		'edit_item'          => __( 'Edit Download', 'gluon-download-manager' ),
		'new_item'           => __( 'New Download', 'gluon-download-manager' ),
		'all_items'          => __( 'Downloads', 'gluon-download-manager' ),
		'view_item'          => __( 'View Download', 'gluon-download-manager' ),
		'search_items'       => __( 'Search Downloads', 'gluon-download-manager' ),
		'not_found'          => __( 'No Downloads found', 'gluon-download-manager' ),
		'not_found_in_trash' => __( 'No Downloads found in Trash', 'gluon-download-manager' ),
		'parent_item_colon'  => __( 'Parent Download', 'gluon-download-manager' ),
		'menu_name'          => __( 'Downloads', 'gluon-download-manager' ),
	);

	$gdm_admin_access_permission = get_gdm_admin_access_permission();
    //Trigger filter hook to allow overriding of the default SDM Post capability.
	$gdm_post_capability = apply_filters( 'gdm_post_type_capability', $gdm_admin_access_permission );
	
	$capabilities = array(
		'edit_post'          => $gdm_post_capability,
		'delete_post'        => $gdm_post_capability,
		'read_post'          => $gdm_post_capability,
		'edit_posts'         => $gdm_post_capability,
		'edit_others_posts'  => $gdm_post_capability,
		'delete_posts'       => $gdm_post_capability,
		'publish_posts'      => $gdm_post_capability,
		'read_private_posts' => $gdm_post_capability,
	);

	$gdm_permalink_base = 'gdm_downloads'; //TODO - add an option to configure in the settings maybe?
	$gdm_slug           = untrailingslashit( $gdm_permalink_base );
	$args               = array(
		'labels'             => $labels,
		'public'             => true,
		'publicly_queryable' => true,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'query_var'          => true,
		'rewrite'            => array( 'slug' => $gdm_slug ),
		'capability_type'    => 'post',
		'capabilities'       => $capabilities,
		'has_archive'        => true,
		'hierarchical'       => false,
		'menu_position'      => null,
		'menu_icon'          => 'dashicons-download',
		'supports'           => array( 'title' ),
	);

	//Trigger filter before registering the post type. Can be used to override the slug of the downloads
	$args = apply_filters( 'gdm_downloads_post_type_before_register', $args );

	register_post_type( 'gdm_downloads', $args );
}

function gdm_create_taxonomies() {

    $gdm_admin_access_permission = get_gdm_admin_access_permission();
	//Trigger filter hook to allow overriding of the default SDM taxonomies capability.
	$gdm_taxonomies_capability = apply_filters( 'gdm_taxonomies_capability', $gdm_admin_access_permission );

	$capabilities = array(
		'manage_terms' 		 => $gdm_taxonomies_capability,
		'edit_terms'   		 => $gdm_taxonomies_capability,
		'delete_terms'  	 => $gdm_taxonomies_capability,
		'assign_terms' 		 => $gdm_taxonomies_capability,
	);

	//*****  Create CATEGORIES Taxonomy
	$labels_tags = array(
		'name'              => __( 'Download Categories', 'gluon-download-manager' ),
		'singular_name'     => __( 'Download Category', 'gluon-download-manager' ),
		'search_items'      => __( 'Search Categories', 'gluon-download-manager' ),
		'all_items'         => __( 'All Categories', 'gluon-download-manager' ),
		'parent_item'       => __( 'Categories Genre', 'gluon-download-manager' ),
		'parent_item_colon' => __( 'Categories Genre:', 'gluon-download-manager' ),
		'edit_item'         => __( 'Edit Category', 'gluon-download-manager' ),
		'update_item'       => __( 'Update Category', 'gluon-download-manager' ),
		'add_new_item'      => __( 'Add New Category', 'gluon-download-manager' ),
		'new_item_name'     => __( 'New Category', 'gluon-download-manager' ),
		'menu_name'         => __( 'Categories', 'gluon-download-manager' ),
	);
	$args_tags   = array(
		'hierarchical'      => true,
		'labels'            => $labels_tags,
		'show_ui'           => true,
		'query_var'         => true,
		'rewrite'           => array( 'slug' => 'gdm_categories' ),
		'show_admin_column' => true,
		'capabilities'      => $capabilities,
	);

	$args_tags = apply_filters( 'gdm_downloads_categories_before_register', $args_tags );

	register_taxonomy( 'gdm_categories', array( 'gdm_downloads' ), $args_tags );

	//*****  Create TAGS Taxonomy
	$labels_tags = array(
		'name'              => __( 'Download Tags', 'gluon-download-manager' ),
		'singular_name'     => __( 'Download Tag', 'gluon-download-manager' ),
		'search_items'      => __( 'Search Tags', 'gluon-download-manager' ),
		'all_items'         => __( 'All Tags', 'gluon-download-manager' ),
		'parent_item'       => __( 'Tags Genre', 'gluon-download-manager' ),
		'parent_item_colon' => __( 'Tags Genre:', 'gluon-download-manager' ),
		'edit_item'         => __( 'Edit Tag', 'gluon-download-manager' ),
		'update_item'       => __( 'Update Tag', 'gluon-download-manager' ),
		'add_new_item'      => __( 'Add New Tag', 'gluon-download-manager' ),
		'new_item_name'     => __( 'New Tag', 'gluon-download-manager' ),
		'menu_name'         => __( 'Tags', 'gluon-download-manager' ),
	);

	$args_tags   = array(
		'hierarchical'      => false,
		'labels'            => $labels_tags,
		'show_ui'           => true,
		'query_var'         => true,
		'rewrite'           => array( 'slug' => 'gdm_tags' ),
		'show_admin_column' => true,
		'capabilities'      => $capabilities,
	);

	$args_tags = apply_filters( 'gdm_downloads_tags_before_register', $args_tags );

	register_taxonomy( 'gdm_tags', array( 'gdm_downloads' ), $args_tags );
}
// Omit closing PHP tag to prevent "headers already sent" errors
