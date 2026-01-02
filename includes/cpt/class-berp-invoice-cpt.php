<?php
/**
 * Invoice Custom Post Type
 *
 * @package BuildErp
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BERP_Invoice_CPT Class
 *
 * Registers and manages the Invoice custom post type
 */
class BERP_Invoice_CPT {


	/**
	 * Register the custom post type
	 *
	 * @since 1.0.0
	 */
	public function register() {
		$labels = array(
			'name'               => _x( 'Invoices', 'Post type general name', 'BuildERP' ),
			'singular_name'      => _x( 'Invoice', 'Post type singular name', 'BuildERP' ),
			'menu_name'          => _x( 'Invoices', 'Admin Menu text', 'BuildERP' ),
			'name_admin_bar'     => _x( 'Invoice', 'Add New on Toolbar', 'BuildERP' ),
			'add_new'            => __( 'Add New', 'BuildERP' ),
			'add_new_item'       => __( 'Add New Invoice', 'BuildERP' ),
			'new_item'           => __( 'New Invoice', 'BuildERP' ),
			'edit_item'          => __( 'Edit Invoice', 'BuildERP' ),
			'view_item'          => __( 'View Invoice', 'BuildERP' ),
			'all_items'          => __( 'All Invoices', 'BuildERP' ),
			'search_items'       => __( 'Search Invoices', 'BuildERP' ),
			'not_found'          => __( 'No invoices found.', 'BuildERP' ),
			'not_found_in_trash' => __( 'No invoices found in Trash.', 'BuildERP' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => false, // We'll add it to custom menu.
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'invoice' ),
			'capability_type'    => 'post',
			'capabilities'       => array(
				'edit_post'          => 'berp_edit_invoices',
				'read_post'          => 'berp_view_invoices',
				'delete_post'        => 'berp_delete_invoices',
				'edit_posts'         => 'berp_edit_invoices',
				'edit_others_posts'  => 'berp_manage_invoices',
				'delete_posts'       => 'berp_delete_invoices',
				'publish_posts'      => 'berp_manage_invoices',
				'read_private_posts' => 'berp_manage_invoices',
			),
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => null,
			'menu_icon'          => 'dashicons-media-spreadsheet',
			'supports'           => array( 'title', 'custom-fields' ),
			'show_in_rest'       => false,
		);

		register_post_type( 'berp_invoice', $args );
	}
}

