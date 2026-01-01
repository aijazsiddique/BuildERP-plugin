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
			'name'               => _x( 'Expenses', 'Post type general name', 'aic_builderp' ),
			'singular_name'      => _x( 'Expense', 'Post type singular name', 'aic_builderp' ),
			'menu_name'          => _x( 'Expenses', 'Admin Menu text', 'aic_builderp' ),
			'name_admin_bar'     => _x( 'Expense', 'Add New on Toolbar', 'aic_builderp' ),
			'add_new'            => __( 'Add New', 'aic_builderp' ),
			'add_new_item'       => __( 'Add New Expense', 'aic_builderp' ),
			'new_item'           => __( 'New Expense', 'aic_builderp' ),
			'edit_item'          => __( 'Edit Expense', 'aic_builderp' ),
			'view_item'          => __( 'View Expense', 'aic_builderp' ),
			'all_items'          => __( 'All Expenses', 'aic_builderp' ),
			'search_items'       => __( 'Search Expenses', 'aic_builderp' ),
			'not_found'          => __( 'No expenses found.', 'aic_builderp' ),
			'not_found_in_trash' => __( 'No expenses found in Trash.', 'aic_builderp' ),
			'featured_image'     => _x( 'Receipt Image', 'Overrides the "Featured Image" phrase', 'aic_builderp' ),
			'set_featured_image' => _x( 'Set receipt image', 'Overrides the "Set featured image" phrase', 'aic_builderp' ),
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
