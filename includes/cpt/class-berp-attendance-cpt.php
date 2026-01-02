<?php
/**
 * Attendance Custom Post Type
 *
 * @package BuildErp
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BERP_Attendance_CPT Class
 *
 * Registers and manages the Attendance custom post type
 * Structure: 1 post = 1 employee + 1 date
 */
class BERP_Attendance_CPT {


	/**
	 * Register the custom post type
	 *
	 * @since 1.0.0
	 */
	public function register() {
		$labels = array(
			'name'               => _x( 'Attendance', 'Post type general name', 'BuildERP' ),
			'singular_name'      => _x( 'Attendance Record', 'Post type singular name', 'BuildERP' ),
			'menu_name'          => _x( 'Attendance', 'Admin Menu text', 'BuildERP' ),
			'name_admin_bar'     => _x( 'Attendance', 'Add New on Toolbar', 'BuildERP' ),
			'add_new'            => __( 'Log Attendance', 'BuildERP' ),
			'add_new_item'       => __( 'Log Attendance', 'BuildERP' ),
			'new_item'           => __( 'New Attendance Record', 'BuildERP' ),
			'edit_item'          => __( 'Edit Attendance Record', 'BuildERP' ),
			'view_item'          => __( 'View Attendance Record', 'BuildERP' ),
			'all_items'          => __( 'View Attendance', 'BuildERP' ),
			'search_items'       => __( 'Search Attendance', 'BuildERP' ),
			'not_found'          => __( 'No attendance records found.', 'BuildERP' ),
			'not_found_in_trash' => __( 'No attendance records found in Trash.', 'BuildERP' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => false, // We'll add it to custom menu.
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'attendance' ),
			'capability_type'    => 'post',
			'capabilities'       => array(
				'edit_post'          => 'berp_edit_attendance',
				'read_post'          => 'berp_view_attendance',
				'delete_post'        => 'berp_delete_attendance',
				'edit_posts'         => 'berp_edit_attendance',
				'edit_others_posts'  => 'berp_manage_attendance',
				'delete_posts'       => 'berp_delete_attendance',
				'publish_posts'      => 'berp_log_attendance',
				'read_private_posts' => 'berp_manage_attendance',
			),
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => null,
			'menu_icon'          => 'dashicons-calendar-alt',
			'supports'           => array( 'custom-fields' ),
			'show_in_rest'       => false,
		);

		register_post_type( 'berp_attendance', $args );
	}
}

