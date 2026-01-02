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
			'name'               => _x( 'Attendance', 'Post type general name', 'builderp' ),
			'singular_name'      => _x( 'Attendance Record', 'Post type singular name', 'builderp' ),
			'menu_name'          => _x( 'Attendance', 'Admin Menu text', 'builderp' ),
			'name_admin_bar'     => _x( 'Attendance', 'Add New on Toolbar', 'builderp' ),
			'add_new'            => __( 'Log Attendance', 'builderp' ),
			'add_new_item'       => __( 'Log Attendance', 'builderp' ),
			'new_item'           => __( 'New Attendance Record', 'builderp' ),
			'edit_item'          => __( 'Edit Attendance Record', 'builderp' ),
			'view_item'          => __( 'View Attendance Record', 'builderp' ),
			'all_items'          => __( 'View Attendance', 'builderp' ),
			'search_items'       => __( 'Search Attendance', 'builderp' ),
			'not_found'          => __( 'No attendance records found.', 'builderp' ),
			'not_found_in_trash' => __( 'No attendance records found in Trash.', 'builderp' ),
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

