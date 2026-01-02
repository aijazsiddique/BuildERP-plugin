<?php
/**
 * Client Custom Post Type
 *
 * @package BuildErp
 * @since   1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BERP_Client_CPT Class
 *
 * Registers and manages the Client custom post type
 */
class BERP_Client_CPT {


	/**
	 * Register the custom post type
	 *
	 * @since 1.0.0
	 */
	public function register() {
		$labels = array(
			'name'               => _x( 'Clients', 'Post type general name', 'BuildERP' ),
			'singular_name'      => _x( 'Client', 'Post type singular name', 'BuildERP' ),
			'menu_name'          => _x( 'Clients', 'Admin Menu text', 'BuildERP' ),
			'name_admin_bar'     => _x( 'Client', 'Add New on Toolbar', 'BuildERP' ),
			'add_new'            => __( 'Add New', 'BuildERP' ),
			'add_new_item'       => __( 'Add New Client', 'BuildERP' ),
			'new_item'           => __( 'New Client', 'BuildERP' ),
			'edit_item'          => __( 'Edit Client', 'BuildERP' ),
			'view_item'          => __( 'View Client', 'BuildERP' ),
			'all_items'          => __( 'All Clients', 'BuildERP' ),
			'search_items'       => __( 'Search Clients', 'BuildERP' ),
			'not_found'          => __( 'No clients found.', 'BuildERP' ),
			'not_found_in_trash' => __( 'No clients found in Trash.', 'BuildERP' ),
		);

		$args = array(
			'labels'             => $labels,
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => false, // We'll add it to custom menu.
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'client' ),
			'capability_type'    => 'post',
			'capabilities'       => array(
				'edit_post'          => 'berp_edit_clients',
				'read_post'          => 'berp_view_clients',
				'delete_post'        => 'berp_delete_clients',
				'edit_posts'         => 'berp_edit_clients',
				'edit_others_posts'  => 'berp_manage_clients',
				'delete_posts'       => 'berp_delete_clients',
				'publish_posts'      => 'berp_manage_clients',
				'read_private_posts' => 'berp_manage_clients',
			),
			'has_archive'        => false,
			'hierarchical'       => false,
			'menu_position'      => null,
			'menu_icon'          => 'dashicons-businessman',
			'supports'           => array( 'title', 'editor', 'custom-fields' ),
			'show_in_rest'       => false,
		);

		register_post_type( 'berp_client', $args );
	}
}

