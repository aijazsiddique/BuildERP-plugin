<?php
/**
 * Advance Request Custom Post Type
 *
 * @package BuildErp
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BERP_Advance_CPT Class
 *
 * Registers and manages the Advance Request custom post type
 */
class BERP_Advance_CPT {

	/**
	 * Register the custom post type
	 *
	 * @since 1.0.0
	 */
	public function register() {
		$labels = array(
			'name'               => _x( 'Salary Advances', 'Post type general name', 'builderp' ),
			'singular_name'      => _x( 'Advance', 'Post type singular name', 'builderp' ),
			'menu_name'          => _x( 'Advances', 'Admin Menu text', 'builderp' ),
			'name_admin_bar'     => _x( 'Advance', 'Add New on Toolbar', 'builderp' ),
			'add_new'            => __( 'Pay Advance', 'builderp' ),
			'add_new_item'       => __( 'Pay Advance to Employee', 'builderp' ),
			'new_item'           => __( 'New Advance Payment', 'builderp' ),
			'edit_item'          => __( 'Edit Advance', 'builderp' ),
			'view_item'          => __( 'View Advance', 'builderp' ),
			'all_items'          => __( 'All Advances', 'builderp' ),
			'search_items'       => __( 'Search Advances', 'builderp' ),
			'not_found'          => __( 'No advances found.', 'builderp' ),
			'not_found_in_trash' => __( 'No advances found in Trash.', 'builderp' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => false, // We'll add it to custom menu.
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'advance' ),
			'capability_type'    => 'post',
			'capabilities'       => array(
				'edit_post'          => 'berp_edit_advances',
				'read_post'          => 'berp_view_advances',
				'delete_post'        => 'berp_delete_advances',
				'edit_posts'         => 'berp_edit_advances',
				'edit_others_posts'  => 'berp_manage_advances',
				'delete_posts'       => 'berp_delete_advances',
				'publish_posts'      => 'berp_manage_advances',
				'read_private_posts' => 'berp_manage_advances',
			),
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => null,
			'menu_icon'          => 'dashicons-money-alt',
			'supports'           => array( 'title', 'custom-fields' ),
			'show_in_rest'       => false,
		);

		register_post_type( 'berp_advance', $args );
	}
}

