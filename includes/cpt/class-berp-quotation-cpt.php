<?php
/**
 * Quotation Custom Post Type
 *
 * @package BuildErp
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BERP_Quotation_CPT Class
 *
 * Registers and manages the Quotation custom post type
 */
class BERP_Quotation_CPT {


	/**
	 * Register the custom post type
	 *
	 * @since 1.0.0
	 */
	public function register() {
		$labels = array(
			'name'               => _x( 'Quotations', 'Post type general name', 'BuildERP' ),
			'singular_name'      => _x( 'Quotation', 'Post type singular name', 'BuildERP' ),
			'menu_name'          => _x( 'Quotations', 'Admin Menu text', 'BuildERP' ),
			'name_admin_bar'     => _x( 'Quotation', 'Add New on Toolbar', 'BuildERP' ),
			'add_new'            => __( 'Add New', 'BuildERP' ),
			'add_new_item'       => __( 'Add New Quotation', 'BuildERP' ),
			'new_item'           => __( 'New Quotation', 'BuildERP' ),
			'edit_item'          => __( 'Edit Quotation', 'BuildERP' ),
			'view_item'          => __( 'View Quotation', 'BuildERP' ),
			'all_items'          => __( 'All Quotations', 'BuildERP' ),
			'search_items'       => __( 'Search Quotations', 'BuildERP' ),
			'not_found'          => __( 'No quotations found.', 'BuildERP' ),
			'not_found_in_trash' => __( 'No quotations found in Trash.', 'BuildERP' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => false, // We'll add it to custom menu.
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'quotation' ),
			'capability_type'    => 'post',
			'capabilities'       => array(
				'edit_post'          => 'berp_edit_quotations',
				'read_post'          => 'berp_view_quotations',
				'delete_post'        => 'berp_delete_quotations',
				'edit_posts'         => 'berp_edit_quotations',
				'edit_others_posts'  => 'berp_manage_quotations',
				'delete_posts'       => 'berp_delete_quotations',
				'publish_posts'      => 'berp_manage_quotations',
				'read_private_posts' => 'berp_manage_quotations',
			),
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => null,
			'menu_icon'          => 'dashicons-media-document',
			'supports'           => array( 'title', 'custom-fields' ),
			'show_in_rest'       => false,
		);

		register_post_type( 'berp_quotation', $args );
	}
}

