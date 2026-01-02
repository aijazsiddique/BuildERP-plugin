<?php
/**
 * Employee Custom Post Type
 *
 * @package BuildErp
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BERP_Employee_CPT Class
 *
 * Registers and manages the Employee custom post type
 */
class BERP_Employee_CPT {


	/**
	 * Register the custom post type
	 *
	 * @since 1.0.0
	 */
	public function register() {
		$labels = array(
			'name'                  => _x( 'Employees', 'Post type general name', 'builderp' ),
			'singular_name'         => _x( 'Employee', 'Post type singular name', 'builderp' ),
			'menu_name'             => _x( 'Employees', 'Admin Menu text', 'builderp' ),
			'name_admin_bar'        => _x( 'Employee', 'Add New on Toolbar', 'builderp' ),
			'add_new'               => __( 'Add New', 'builderp' ),
			'add_new_item'          => __( 'Add New Employee', 'builderp' ),
			'new_item'              => __( 'New Employee', 'builderp' ),
			'edit_item'             => __( 'Edit Employee', 'builderp' ),
			'view_item'             => __( 'View Employee', 'builderp' ),
			'all_items'             => __( 'All Employees', 'builderp' ),
			'search_items'          => __( 'Search Employees', 'builderp' ),
			'parent_item_colon'     => __( 'Parent Employees:', 'builderp' ),
			'not_found'             => __( 'No employees found.', 'builderp' ),
			'not_found_in_trash'    => __( 'No employees found in Trash.', 'builderp' ),
			'featured_image'        => _x( 'Employee Photo', 'Overrides the "Featured Image" phrase', 'builderp' ),
			'set_featured_image'    => _x( 'Set employee photo', 'Overrides the "Set featured image" phrase', 'builderp' ),
			'remove_featured_image' => _x( 'Remove employee photo', 'Overrides the "Remove featured image" phrase', 'builderp' ),
			'use_featured_image'    => _x( 'Use as employee photo', 'Overrides the "Use as featured image" phrase', 'builderp' ),
			'archives'              => _x( 'Employee archives', 'The post type archive label', 'builderp' ),
			'insert_into_item'      => _x( 'Insert into employee', 'Overrides the "Insert into post" phrase', 'builderp' ),
			'uploaded_to_this_item' => _x( 'Uploaded to this employee', 'Overrides the "Uploaded to this post" phrase', 'builderp' ),
			'filter_items_list'     => _x( 'Filter employees list', 'Screen reader text', 'builderp' ),
			'items_list_navigation' => _x( 'Employees list navigation', 'Screen reader text', 'builderp' ),
			'items_list'            => _x( 'Employees list', 'Screen reader text', 'builderp' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => false, // We'll add it to custom menu.
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'employee' ),
			'capability_type'    => 'post',
			'capabilities'       => array(
				'edit_post'          => 'berp_edit_employees',
				'read_post'          => 'berp_view_employees',
				'delete_post'        => 'berp_delete_employees',
				'edit_posts'         => 'berp_edit_employees',
				'edit_others_posts'  => 'berp_manage_employees',
				'delete_posts'       => 'berp_delete_employees',
				'publish_posts'      => 'berp_manage_employees',
				'read_private_posts' => 'berp_manage_employees',
			),
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => null,
			'menu_icon'          => 'dashicons-groups',
			'supports'           => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
			'show_in_rest'       => false,
		);

		register_post_type( 'berp_employee', $args );
	}
}

