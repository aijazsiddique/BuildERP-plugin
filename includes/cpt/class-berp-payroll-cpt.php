<?php
/**
 * Payroll Custom Post Type
 *
 * @package BuildErp
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BERP_Payroll_CPT Class
 *
 * Registers and manages the Payroll custom post type
 */
class BERP_Payroll_CPT {


	/**
	 * Register the custom post type
	 *
	 * @since 1.0.0
	 */
	public function register() {
		$labels = array(
			'name'               => _x( 'Payroll', 'Post type general name', 'BuildERP' ),
			'singular_name'      => _x( 'Payroll Record', 'Post type singular name', 'BuildERP' ),
			'menu_name'          => _x( 'Payroll', 'Admin Menu text', 'BuildERP' ),
			'name_admin_bar'     => _x( 'Payroll', 'Add New on Toolbar', 'BuildERP' ),
			'add_new'            => __( 'Process Payroll', 'BuildERP' ),
			'add_new_item'       => __( 'Process Payroll', 'BuildERP' ),
			'new_item'           => __( 'New Payroll Record', 'BuildERP' ),
			'edit_item'          => __( 'Edit Payroll Record', 'BuildERP' ),
			'view_item'          => __( 'View Payroll Record', 'BuildERP' ),
			'all_items'          => __( 'All Payroll', 'BuildERP' ),
			'search_items'       => __( 'Search Payroll', 'BuildERP' ),
			'not_found'          => __( 'No payroll records found.', 'BuildERP' ),
			'not_found_in_trash' => __( 'No payroll records found in Trash.', 'BuildERP' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => false, // We'll add it to custom menu.
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'payroll' ),
			'capability_type'    => 'post',
			'capabilities'       => array(
				'edit_post'          => 'berp_edit_payroll',
				'read_post'          => 'berp_view_payroll',
				'delete_post'        => 'berp_delete_payroll',
				'edit_posts'         => 'berp_edit_payroll',
				'edit_others_posts'  => 'berp_manage_payroll',
				'delete_posts'       => 'berp_delete_payroll',
				'publish_posts'      => 'berp_process_payroll',
				'read_private_posts' => 'berp_manage_payroll',
			),
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => null,
			'menu_icon'          => 'dashicons-money-alt',
			'supports'           => array( 'custom-fields' ),
			'show_in_rest'       => false,
		);

		register_post_type( 'berp_payroll', $args );
	}
}

