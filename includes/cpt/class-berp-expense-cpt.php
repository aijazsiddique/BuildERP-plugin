<?php
/**
 * Expense Custom Post Type
 *
 * @package BuildErp
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BERP_Expense_CPT Class
 *
 * Registers and manages the Expense custom post type
 */
class BERP_Expense_CPT {


	/**
	 * Register the custom post type
	 *
	 * @since 1.0.0
	 */
	public function register() {
		$labels = array(
			'name'               => _x( 'Expenses', 'Post type general name', 'builderp' ),
			'singular_name'      => _x( 'Expense', 'Post type singular name', 'builderp' ),
			'menu_name'          => _x( 'Expenses', 'Admin Menu text', 'builderp' ),
			'name_admin_bar'     => _x( 'Expense', 'Add New on Toolbar', 'builderp' ),
			'add_new'            => __( 'Add New', 'builderp' ),
			'add_new_item'       => __( 'Add New Expense', 'builderp' ),
			'new_item'           => __( 'New Expense', 'builderp' ),
			'edit_item'          => __( 'Edit Expense', 'builderp' ),
			'view_item'          => __( 'View Expense', 'builderp' ),
			'all_items'          => __( 'All Expenses', 'builderp' ),
			'search_items'       => __( 'Search Expenses', 'builderp' ),
			'not_found'          => __( 'No expenses found.', 'builderp' ),
			'not_found_in_trash' => __( 'No expenses found in Trash.', 'builderp' ),
			'featured_image'     => _x( 'Receipt Image', 'Overrides the "Featured Image" phrase', 'builderp' ),
			'set_featured_image' => _x( 'Set receipt image', 'Overrides the "Set featured image" phrase', 'builderp' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => false, // We'll add it to custom menu.
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'expense' ),
			'capability_type'    => 'post',
			'capabilities'       => array(
				'edit_post'          => 'berp_edit_expenses',
				'read_post'          => 'berp_view_expenses',
				'delete_post'        => 'berp_delete_expenses',
				'edit_posts'         => 'berp_edit_expenses',
				'edit_others_posts'  => 'berp_manage_expenses',
				'delete_posts'       => 'berp_delete_expenses',
				'publish_posts'      => 'berp_manage_expenses',
				'read_private_posts' => 'berp_manage_expenses',
			),
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => null,
			'menu_icon'          => 'dashicons-money',
			'supports'           => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
			'show_in_rest'       => false,
		);

		register_post_type( 'berp_expense', $args );
	}
}

